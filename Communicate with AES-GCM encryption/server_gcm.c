#include<stdio.h>
#include<string.h>
#include<sys/socket.h>
#include<arpa/inet.h>
#include<errno.h>
#include<unistd.h>
#include <openssl/conf.h>
#include <openssl/evp.h>
#include <openssl/err.h>

int gcm_decrypt(unsigned char* ciphertext, int ciphertext_len, unsigned char* aad, int aad_len, unsigned char* tag, unsigned char* key, unsigned char* iv, int iv_len, unsigned char* plaintext)
{
	EVP_CIPHER_CTX* ctx;
	int len, plaintext_len, ret;

	/* Create and initialize the context */
	ctx = EVP_CIPHER_CTX_new();
	if (!ctx) {
		// Handle error
		return -1;
	}

	/* Initialize the decryption operation. */
	EVP_DecryptInit_ex(ctx, EVP_aes_256_gcm(), NULL, NULL, NULL);

	/* Set IV length. Not necessary if this is 12 bytes (96 bits) */
	EVP_CIPHER_CTX_ctrl(ctx, EVP_CTRL_GCM_SET_IVLEN, iv_len, NULL);

	/* Initialize key and IV */
	EVP_DecryptInit_ex(ctx, NULL, NULL, key, iv);

	/*
	 * Provide any AAD data. This can be called zero or more times as
	 * required
	 */
	EVP_DecryptUpdate(ctx, NULL, &len, aad, aad_len);

	/*
	 * Provide the message to be decrypted, and obtain the plaintext output.
	 * EVP_DecryptUpdate can be called multiple times if necessary
	 */
	EVP_DecryptUpdate(ctx, plaintext, &len, ciphertext, ciphertext_len);
	plaintext_len = len;

	/* Set expected tag value. Works in OpenSSL 1.0.1d and later */
	EVP_CIPHER_CTX_ctrl(ctx, EVP_CTRL_GCM_SET_TAG, 16, tag);

	/*
	 * Finalize the decryption. A positive return value indicates success,
	 * and anything else is a failure - the plaintext is not trustworthy.
	 */
	ret = EVP_DecryptFinal_ex(ctx, plaintext + len, &len);

	/* Clean up */
	EVP_CIPHER_CTX_free(ctx);

	if (ret > 0) {
		/* Success */
		plaintext_len += len;
		return plaintext_len;
	}
	else {
		/* Verify failed */
		return -1;
	}
}


int main(void){

	//Declare variables
	const int server_port = 2023;
	const char* ip = "192.168.50.131";
	struct sockaddr_in serverAddr;
	serverAddr.sin_family = AF_INET;
	serverAddr.sin_port = htons(server_port);
	serverAddr.sin_addr.s_addr = inet_addr(ip);

	//Create socket
	int socketfd = socket(AF_INET, SOCK_STREAM, 0);

	//Bind to the set port and ip
	int bind_result = bind(socketfd, (struct sockaddr *) &serverAddr, sizeof(serverAddr));
	if (bind_result == 0){
		printf("Done with binding with IP: %s, Port: %d\n", "192.168.50.131", server_port);
	}
	else{
		printf("Failed with binding, please check the following error.\n");
		printf("errno = %d\n", errno);
		return 0;
	}

	//Listen for client
	int listen_result = listen(socketfd, 5);

	//Accept an incoming connection
	struct sockaddr_in clientAddr;
	socklen_t clientAddr_size = sizeof(clientAddr);
	printf("Wait for the client to connect.\n");
	int accept_result = accept(socketfd, (struct sockaddr*) &clientAddr, &clientAddr_size);
	if (accept_result == -1){
		printf("Failed accept client, please check the following error.\n");
		printf("errno = %d\n", errno);
	}
	else{
		printf("Client with IP: %s and Port: %d connects to the server\n", inet_ntoa(clientAddr.sin_addr), clientAddr.sin_port);
	}
	printf("====================================\n");

	//Receive and respond client's message
	char recv_buffer[256] = {0};
	//set key and IV
	char send_buffer[50] = "Server received your message";
	unsigned char key[32] = "24531497389627489993906020668418";
	unsigned char iv[12] = "495871400285";
	int iv_len = sizeof(iv);
	unsigned char tag[16];
	unsigned char aad[] = "Additional Authentication Data";
	int aad_len = sizeof(aad);
	int plaintext_len = 256;
	int sendBuffer_size = sizeof(send_buffer);
	int send_result;
	//receive ciphertext
	int receive_result = recv(accept_result, recv_buffer, sizeof(recv_buffer), 0);
	//receive tag
	int receive_tag_result = recv(accept_result, tag, sizeof(tag), 0);
	char plaintext[256];
	while(1){
		if (receive_result > 0 && receive_tag_result > 0){
			//decrypt
			int decrypted_len = gcm_decrypt(recv_buffer, 256, aad, aad_len, tag, key, iv, iv_len, plaintext);
			if (decrypted_len == -1) {
				//authenticaction failed
				printf("The message is not trustworthy\n");
				printf("Authentication: Failed\n");
			}
			else {
				//exit
				if (plaintext[0] == 'e' && plaintext[1] == 'x' && plaintext[2] == 'i' && plaintext[3] == 't') {
					printf("Client ends the communication\n");
					break;
				}
				else {
					//print the message
					printf("Authentication: Successful\n");
					printf("MSG from client: %s", plaintext);
				}
			}
			send_result = send(accept_result, send_buffer, sendBuffer_size, 0);
			if (send_result == 0) {
				printf("Send response failed.");
			}
			printf("====================================\n");

		}
		else if (receive_result == 0){
			printf("Client shutdown the connection");
			break;
		}
		else{
			printf("Failed to receive MSG from client, please check the following error. \n");
			printf("errno = %d\n", errno);
			break;
		}
		//reset
		memset(recv_buffer, 0, 256);
		memset(tag, 0, 16);
		memset(plaintext, 0, 256);
		receive_result = recv(accept_result, recv_buffer, sizeof(recv_buffer), 0);
		receive_tag_result = recv(accept_result, tag, sizeof(tag), 0);
	}
			
	//Close socket
	close(socketfd);
	close(accept_result);
}
