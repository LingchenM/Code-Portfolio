#pragma once
#include"AbstractCommand.h"
class AbstractFileFactory;
class AbstractFileSystem;

class DisplayCommand : public AbstractCommand {
private:
	AbstractFileSystem* AFS;
public:
	DisplayCommand(AbstractFileSystem* AFS);
	void displayInfo();
	int execute(string s);
	~DisplayCommand() = default;
};