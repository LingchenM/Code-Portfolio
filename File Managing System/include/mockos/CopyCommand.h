#pragma once
#include"AbstractCommand.h"
class AbstractFileFactory;
class AbstractFileSystem;

class CopyCommand : public AbstractCommand {
private:
	AbstractFileSystem* AFS;
public:
	CopyCommand(AbstractFileSystem* AFS);
	void displayInfo();
	int execute(string s);
	~CopyCommand() = default;
};