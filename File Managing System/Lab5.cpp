#include"mockos/AbstractCommand.h"
#include"mockos/AbstractFile.h"
#include"mockos/AbstractFileFactory.h"
#include"mockos/AbstractFileSystem.h"
#include"mockos/SimpleFileSystem.h"
#include"mockos/SimpleFileFactory.h"
#include"mockos/TouchCommand.h"
#include"mockos/LSCommand.h"
#include"mockos/RemoveCommand.h"
#include"mockos/CatCommand.h"
#include"mockos/DisplayCommand.h"
#include"mockos/CopyCommand.h"
#include"mockos/AbstractParsingStrategy.h"
#include"mockos/MacroCommand.h"
#include"mockos/RenameParsingStrategy.h"
#include"mockos/TCParsingStrategy.h"
#include"mockos/CommandPrompt.h"

int main(int argc, char* argv[]) {
	AbstractFileSystem* AFS = new SimpleFileSystem;
	AbstractFileFactory* AFF = new SimpleFileFactory;
	AbstractCommand* TC = new TouchCommand(AFS, AFF);
	AbstractCommand* LS = new LSCommand(AFS);
	AbstractCommand* RM = new RemoveCommand(AFS);
	AbstractCommand* CAT = new CatCommand(AFS);
	AbstractCommand* Dis = new DisplayCommand(AFS);
	AbstractCommand* Copy = new CopyCommand(AFS);
	MacroCommand* MC1 = new MacroCommand(AFS);
	AbstractParsingStrategy* APS1 = new RenameParsingStrategy();
	MC1->addCommand(Copy);
	MC1->addCommand(RM);
	MC1->setParseStrategy(APS1);
	MacroCommand* MC2 = new MacroCommand(AFS);
	AbstractParsingStrategy* APS2 = new TCParsingStrategy();
	MC2->addCommand(TC);
	MC2->addCommand(CAT);
	MC2->setParseStrategy(APS2);
	CommandPrompt CP;
	CP.setFileFactory(AFF);
	CP.setFileSystem(AFS);
	CP.addCommand("touch", TC);
	CP.addCommand("ls", LS);
	CP.addCommand("rm", RM);
	CP.addCommand("cat", CAT);
	CP.addCommand("ds", Dis);
	CP.addCommand("cp", Copy);
	CP.addCommand("rn", MC1);
	CP.addCommand("tc", MC2);
    int outcome = CP.run();
    delete AFS;
    delete AFF;
    delete TC;
    delete LS;
    delete RM;
    delete CAT;
    delete Dis;
    delete Copy;
    delete MC1;
    delete APS1;
    delete MC2;
    delete APS2;

    return outcome;
}