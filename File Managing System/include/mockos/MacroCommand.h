#pragma once
#include"AbstractCommand.h"
#include"AbstractParsingStrategy.h"
#include<vector>

class AbstractParsingStrategy;
class AbstractFileSystem;

class MacroCommand : public AbstractCommand {
private:
	AbstractFileSystem* AFS;
	vector<AbstractCommand*> v;
	AbstractParsingStrategy* APS;
public:
	MacroCommand(AbstractFileSystem* AFS);
	void displayInfo();
	int execute(string s);
	int addCommand(AbstractCommand* AC);
	int setParseStrategy(AbstractParsingStrategy* APS);
	~MacroCommand() = default;
};