#pragma once
#include "string"
#include "AbstractFile.h"
using namespace std;

class AbstractFileFactory{
public:
    virtual AbstractFile* createFile(string) = 0;
};