#pragma once
#include"AbstractCommand.h"
class AbstractFileFactory;
class AbstractFileSystem;

class RemoveCommand : public AbstractCommand {
private:
	AbstractFileSystem* AFS;
public:
	RemoveCommand(AbstractFileSystem* AFS);
	void displayInfo();
	int execute(string s);
	~RemoveCommand() = default;
};