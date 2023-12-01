#pragma once
#include<map>
#include<string>
#include "AbstractFileSystem.h"
#include "AbstractFileFactory.h"
class AbstractCommand;

using namespace std;

class CommandPrompt {
private:
	map<string, AbstractCommand*> command;
	AbstractFileSystem* system;
	AbstractFileFactory* factory;
public:
	CommandPrompt();
	void setFileSystem(AbstractFileSystem*);
	void setFileFactory(AbstractFileFactory*);
	int addCommand(std::string, AbstractCommand*);
	int run();
protected:
	void listCommands();
	string prompt();
};