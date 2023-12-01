#pragma once
#include"AbstractCommand.h"
class AbstractFileFactory;
class AbstractFileSystem;

class CatCommand : public AbstractCommand {
private:
	AbstractFileSystem* AFS;
public:
	CatCommand(AbstractFileSystem* AFS);
	void displayInfo();
	int execute(string s);
	~CatCommand() = default;
};