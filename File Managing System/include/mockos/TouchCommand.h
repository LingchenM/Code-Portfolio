#pragma once
#include"AbstractCommand.h"
class AbstractFileFactory;
class AbstractFileSystem;

class TouchCommand : public AbstractCommand {
private:
	AbstractFileSystem* AFS;
	AbstractFileFactory* AFF;
public:
	TouchCommand(AbstractFileSystem* a, AbstractFileFactory* b);
	void displayInfo();
	int execute(string s);
	~TouchCommand() = default;
};