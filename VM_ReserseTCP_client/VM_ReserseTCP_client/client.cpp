#undef UNICODE
#define WIN32_LEAN_AND_MEAN

#include <winsock2.h>
#include <tlhelp32.h>
#include <stdbool.h>
#include <string.h>
#include <iostream>
#include <stdio.h>
#include <windows.h>
#include <ws2tcpip.h>
#include <setupapi.h>
#include <vector>
#include <iphlpapi.h>

#pragma comment (lib, "Ws2_32.lib")
#pragma comment (lib, "Mswsock.lib")
#pragma comment (lib, "AdvApi32.lib")
#pragma comment (lib, "setupapi.lib")
#pragma comment (lib, "iphlpapi.lib")

#define DEFAULT_PORT "27015"
#define DEFAULT_BUFLEN 512

using namespace std;

bool check_process_is_running(const std::string& proc_name) {
    HANDLE hSnapshot;
    PROCESSENTRY32 pe = {};

    pe.dwSize = sizeof(pe);
    bool present = false;
    hSnapshot = CreateToolhelp32Snapshot(TH32CS_SNAPPROCESS, 0);

    if (hSnapshot == INVALID_HANDLE_VALUE)
        return false;

    if (Process32First(hSnapshot, &pe)) {
        while (Process32Next(hSnapshot, &pe)) {
            if (!_strcmpi(pe.szExeFile, proc_name.c_str())) {
                present = true;
                break;
            }
        }
    }
    CloseHandle(hSnapshot);

    return present;
}

bool check_VMproc_exist() {
    if (check_process_is_running("VboxTray.exe") || 
        check_process_is_running("vmwaretray.exe") || 
        check_process_is_running("xenservice.exe")) {
        return true;
    }
    else {
        return false;
    }
}

bool check_HDD_name() {

    HDEVINFO hDevInfo;
    SP_DEVINFO_DATA DeviceInfoData;
    DWORD i;
    // GUID of the DiskDrive class
    GUID DiskDriveGUID = { 0x4d36e967, 0xe325, 0x11ce, {0xbf, 0xc1, 0x08, 0x00, 0x2b, 0xe1, 0x03, 0x18} };

    // Get a handle to the device information set for all devices matching class GUID.
    hDevInfo = SetupDiGetClassDevs(&DiskDriveGUID, NULL, NULL, DIGCF_PRESENT);
    if (hDevInfo == INVALID_HANDLE_VALUE) {
        // Handle error.
        return false;
    }

    // Enumerate through all devices in the set.
    DeviceInfoData.cbSize = sizeof(SP_DEVINFO_DATA);
    for (i = 0; SetupDiEnumDeviceInfo(hDevInfo, i, &DeviceInfoData); i++) {
        DWORD dataSize = 0;

        // First call gets the size of the friendly name
        SetupDiGetDeviceRegistryProperty(
            hDevInfo,
            &DeviceInfoData,
            SPDRP_FRIENDLYNAME,
            NULL,
            NULL,
            0,
            &dataSize);

        // Allocate buffer to hold the friendly name
        std::vector<TCHAR> buffer(dataSize / sizeof(TCHAR));

        // Retrieve the friendly name
        if (SetupDiGetDeviceRegistryProperty(
            hDevInfo,
            &DeviceInfoData,
            SPDRP_FRIENDLYNAME,
            NULL,
            reinterpret_cast<PBYTE>(buffer.data()),
            dataSize,
            &dataSize)) {

            std::wstring deviceName(buffer.begin(), buffer.end());
            // Now we have the friendly name. Check if it contains "vbox".
            if (deviceName.find(L"VBOX") != std::wstring::npos || 
                deviceName.find(L"VMware") != std::wstring::npos ||
                deviceName.find(L"QEMU") != std::wstring::npos ||
                deviceName.find(L"VIRTUAL HD") != std::wstring::npos) {
                SetupDiDestroyDeviceInfoList(hDevInfo);
                return true;
            }
        }
    }

    if (GetLastError() != NO_ERROR && GetLastError() != ERROR_NO_MORE_ITEMS) {
        // Handle error.
        SetupDiDestroyDeviceInfoList(hDevInfo);
        return false;
    }

    // Cleanup
    SetupDiDestroyDeviceInfoList(hDevInfo);
    return false;
}

std::string FormatMACAddress(const BYTE* mac, DWORD size) {
    std::string formattedMAC;
    for (DWORD i = 0; i < size; i++) {
        char byteStr[3];
        sprintf_s(byteStr, "%02X", mac[i]);
        formattedMAC += byteStr;
        if (i != (size - 1)) {
            formattedMAC += ":";
        }
    }
    return formattedMAC;
}

bool CheckMACAddress(const BYTE* mac) {
    return mac[0] == 0x08 && mac[1] == 0x00 && mac[2] == 0x27;
}

bool check_VM_MAC() {
    DWORD dwRetVal = 0;
    ULONG flags = GAA_FLAG_INCLUDE_PREFIX;
    ULONG outBufLen = 0;
    PIP_ADAPTER_ADDRESSES pAddresses = nullptr;

    // First, retrieve the required buffer size
    dwRetVal = GetAdaptersAddresses(AF_UNSPEC, flags, nullptr, pAddresses, &outBufLen);
    if (dwRetVal == ERROR_BUFFER_OVERFLOW) {
        pAddresses = reinterpret_cast<PIP_ADAPTER_ADDRESSES>(new BYTE[outBufLen]);
    }
    else {
        printf("GetAdaptersAddresses failed with error : %d", dwRetVal);
        return true;
    }

    // Retrieve the adapter addresses
    dwRetVal = GetAdaptersAddresses(AF_UNSPEC, flags, nullptr, pAddresses, &outBufLen);
    if (dwRetVal != NO_ERROR) {
        printf("GetAdaptersAddresses failed with error: %d", dwRetVal);
        delete[] reinterpret_cast<BYTE*>(pAddresses);
        return true;
    }

    // Iterate through all of the adapters
    for (PIP_ADAPTER_ADDRESSES pCurrAddresses = pAddresses; pCurrAddresses != nullptr; pCurrAddresses = pCurrAddresses->Next) {
        if (CheckMACAddress(pCurrAddresses->PhysicalAddress)) {
            return true;
        }
    }

    // Clean up
    delete[] reinterpret_cast<BYTE*>(pAddresses);
    return false;
}

bool check_env() {
    bool proc_check = check_VMproc_exist();
    bool HDD_check = check_HDD_name();
    bool VM_mac_result = check_VM_MAC();
    if (proc_check == false && HDD_check == false && VM_mac_result == false) {
        return true;
    }
    else {
        printf("proc check result: %i\n", proc_check);
        printf("HDD check result: %i\n", HDD_check);
        printf("MAC check result: %i\n", VM_mac_result);
        return false;
    }
}

int client_socket() {

    WSADATA wsaData;
    int iResult;

    // Initialize Winsock
    iResult = WSAStartup(MAKEWORD(2, 2), &wsaData);
    if (iResult != 0) {
        printf("WSAStartup failed: %d\n", iResult);
        return 1;
    }

    struct addrinfo* result = NULL, * ptr = NULL, hints;

    ZeroMemory(&hints, sizeof(hints));
    hints.ai_family = AF_INET;
    hints.ai_socktype = SOCK_STREAM;
    hints.ai_protocol = IPPROTO_TCP;

    // Resolve the server address and port
    // iResult = getaddrinfo(argv[1], DEFAULT_PORT, &hints, &result);
    iResult = getaddrinfo("10.232.205.241", DEFAULT_PORT, &hints, &result);
    if (iResult != 0) {
        printf("getaddrinfo failed: %d\n", iResult);
        WSACleanup();
        return 1;
    }

    SOCKET ConnectSocket = INVALID_SOCKET;
    ptr = result;

    // Create a SOCKET for connecting to server
    ConnectSocket = socket(ptr->ai_family, ptr->ai_socktype, ptr->ai_protocol);

    if (ConnectSocket == INVALID_SOCKET) {
        printf("Error at socket(): %ld\n", WSAGetLastError());
        freeaddrinfo(result);
        WSACleanup();
        return 1;
    }

    // Connect to server.
    iResult = connect(ConnectSocket, ptr->ai_addr, (int)ptr->ai_addrlen);
    if (iResult == SOCKET_ERROR) {
        closesocket(ConnectSocket);
        ConnectSocket = INVALID_SOCKET;
    }

    freeaddrinfo(result);

    if (ConnectSocket == INVALID_SOCKET) {
        printf("Unable to connect to server!\n");
        WSACleanup();
        return 1;
    }

    int recvbuflen = DEFAULT_BUFLEN;

    const char* sendbuf = "this is a test";
    char recvbuf[DEFAULT_BUFLEN];

    // Send an initial buffer
    iResult = send(ConnectSocket, sendbuf, (int)strlen(sendbuf), 0);
    if (iResult == SOCKET_ERROR) {
        printf("send failed: %d\n", WSAGetLastError());
        closesocket(ConnectSocket);
        WSACleanup();
        return 1;
    }

    printf("Bytes Sent: %ld\n", iResult);

    // shutdown the connection for sending since no more data will be sent
    iResult = shutdown(ConnectSocket, SD_SEND);
    if (iResult == SOCKET_ERROR) {
        printf("shutdown failed: %d\n", WSAGetLastError());
        closesocket(ConnectSocket);
        WSACleanup();
        return 1;
    }

    // Receive data until the server closes the connection
    while (iResult > 0) {
        iResult = recv(ConnectSocket, recvbuf, recvbuflen, 0);
        if (iResult > 0)
            printf("Bytes received: %d\n", iResult);
        else if (iResult == 0)
            printf("Connection closed\n");
        else
            printf("recv failed: %d\n", WSAGetLastError());
    }

    // cleanup
    closesocket(ConnectSocket);
    WSACleanup();

    return 0;

}

int main(int argc, char* argv[]) {
    if (check_env()) {
        printf("safe to perform malicious activity.");
        return 0; //client_socket();
    }
    else {
        printf("NOT safe to perform malicious activity.");
        return 1;
    }
}
