#pragma once
#include "AbstractFileSystem.h"
#include <map>
#include <set>
using namespace std;
class SimpleFileSystem : public AbstractFileSystem{
private:
    map<string,AbstractFile*> fileSystem;
    set<AbstractFile*> openfile;
public:
    int addFile(string, AbstractFile *) override;
//    int createFile(string) override;
    AbstractFile * openFile(string) override;
    int closeFile(AbstractFile *) override;
    int deleteFile(string) override;
    set<string> getFileNames() override;
    ~SimpleFileSystem() override;
};