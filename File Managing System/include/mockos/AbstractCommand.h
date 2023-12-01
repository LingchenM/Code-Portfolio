#pragma once
#include<string>

using namespace std;

class AbstractCommand {
public:
	virtual ~AbstractCommand() = default;
	virtual int execute(string) = 0;
	virtual void displayInfo() = 0;
};