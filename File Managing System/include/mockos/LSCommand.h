#pragma once
#include"AbstractCommand.h"
class AbstractFileFactory;
class AbstractFileSystem;

class LSCommand : public AbstractCommand {
private:
	AbstractFileSystem* AFS;
public:
	LSCommand(AbstractFileSystem* AFS);
	void displayInfo();
	int execute(string s);
	~LSCommand() = default;
};