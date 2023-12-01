#pragma once
#include<iostream>
#include"AbstractFile.h"

using namespace std;

class PasswordProxy : public AbstractFile{
private:
	AbstractFile* p;
	string password;
public:
	PasswordProxy(AbstractFile* p, string password);
	~PasswordProxy();
	vector<char> read();
	int write(vector<char>);
	int append(vector<char>);
	unsigned int getSize();
	string getName();
	void accept(AbstractFileVisitor*);
	AbstractFile* clone(string s);
protected:
	string passwordPrompt();
	bool isCorrectPass(string input);
};