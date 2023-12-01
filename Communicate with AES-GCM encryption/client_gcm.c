#include<stdio.h>
#include<string.h>
#include<sys/socket.h>
#include<arpa/inet.h>
#include<errno.h>
#include<unistd.h>
#include <openssl/conf.h>
#include <openssl/evp.h>
#include <openssl/err.h>

int gcm_encrypt(unsigned char* plaintext, int plaintext_len, unsigned char* aad, int aad_len, unsigned char* key, unsigned char* iv, int iv_len, unsigned char* ciphertext, unsigned char* tag)
{
	EVP_CIPHER_CTX* ctx;
	int len, ciphertext_len;

	/* Create and initialize the context */
	ctx = EVP_CIPHER_CTX_new();
	if (!ctx) {
		// Handle error
		return -1;
	}

	/* Initialize the encryption operation. */
	EVP_EncryptInit_ex(ctx, EVP_aes_256_gcm(), NULL, NULL, NULL);

	/*
	 * Set IV length if default 12 bytes (96 bits) is not appropriate
	 */
	EVP_CIPHER_CTX_ctrl(ctx, EVP_CTRL_GCM_SET_IVLEN, iv_len, NULL);

	/* Initialize key and IV */

	EVP_EncryptInit_ex(ctx, NULL, NULL, key, iv);

	/*
	* Provide any AAD data. This can be called zero or more times as
	* required
	*/
	EVP_EncryptUpdate(ctx, NULL, &len, aad, aad_len);

	/*
	 * Provide the message to be encrypted, and obtain the encrypted output.
	 * EVP_EncryptUpdate can be called multiple times if necessary
	 */
	EVP_EncryptUpdate(ctx, ciphertext, &len, plaintext, plaintext_len);
	ciphertext_len = len;

	/*
	 * Finalize the encryption. Normally ciphertext bytes may be written at
	 * this stage, but this does not occur in GCM mode
	 */
	EVP_EncryptFinal_ex(ctx, ciphertext + len, &len);
	ciphertext_len += len;

	/* Get the tag */
	EVP_CIPHER_CTX_ctrl(ctx, EVP_CTRL_GCM_GET_TAG, 16, tag);

	/* Clean up */
	EVP_CIPHER_CTX_free(ctx);

	return ciphertext_len;
}


int main(void){
	//Declare variables
	char client_message[256] = {0};
	int server_port = 2023;
	struct sockaddr_in serverAddr;
	serverAddr.sin_family = AF_INET;
	serverAddr.sin_port = htons(server_port);
	serverAddr.sin_addr.s_addr = inet_addr("192.168.50.131");

	//Create socket
	int socketfd = socket(AF_INET, SOCK_STREAM, 0);

	//Send request to server
	int connect_server = connect(socketfd, (struct sockaddr*) &serverAddr, sizeof(serverAddr));
	if (connect_server != 0){
		printf("Failed to connect to server, please ckeck the following error:\n");
		printf("errno = %d", errno);
		return -1;
	}
	else{
		printf("Connected to the server with IP: %s and Port: %d\n", inet_ntoa(serverAddr.sin_addr), serverAddr.sin_port);
		printf("You can now send message to the server. Enter 'exit' to end the session.\n");
	}

	//Get input from the user
	int cipher_send_result;
	int tag_send_result;
	char recv_buffer[256] = {0};
	int receive_result;

	//set key, IV, add and tag
	unsigned char key[32] = "24531497389627489993906020668418";
	unsigned char iv[12] = "495871400285";
	unsigned char ciphertext[256];
	unsigned char tag[16];
	unsigned char aad[] = "Additional Authentication Data";
	int aad_len = sizeof(aad);
	int plaintext_len = 256;

	while(1){
		printf("Enter message to the server: ");
		fgets(client_message, 256, stdin);
		
		//encrypt
		int cipher_len = gcm_encrypt(client_message, plaintext_len, aad, aad_len, key, iv, sizeof(iv), ciphertext, tag);
		//send ciphertext
		cipher_send_result = send(socketfd, ciphertext, sizeof(ciphertext), 0);
		//send tag
		tag_send_result = send(socketfd, tag, sizeof(tag), 0);
		if (cipher_send_result == -1 || tag_send_result == -1){
			printf("Failed to send message or tag to the server, please ckeck the following error.\n");
			printf("errno = %d", errno);
			return -1;
		}
		//exit
		if (client_message[0] == 'e' && client_message[1] == 'x' && client_message[2] == 'i' && client_message[3] == 't'){
			printf("You end the communication with the server.\n");
			break;
		}
		//print ciphertext and tag
		printf("ciphertext: \n---------------------------------------\n");
		for (int i = 0; i < cipher_len; i++) {
			printf("%02x", ciphertext[i]);
		}
		printf("\n---------------------------------------\n");
		printf("tag value: ");
		for (int i = 0; i < 16; i++) {
			printf("%02x", tag[i]);
		}
		printf("\n");
		//Receive the server's response
		receive_result = recv(socketfd, recv_buffer, sizeof(recv_buffer), 0);
		if (receive_result > 0){
			printf("Server's response: %s\n",recv_buffer);
		}
		else if (receive_result == 0){
			printf("The server shutdown the connection\n");
			break;
		}
		else{
			printf("Failed to receive server's message, please check the following error.\n");
			printf("errno = %d", errno);
			return -1;
		}
		//reset
		memset(client_message, 0, 256);
		memset(ciphertext, 0, 256);
		memset(tag, 0, 16);
		printf("====================================\n");
	}
	//Close socket
	close(socketfd);
}

