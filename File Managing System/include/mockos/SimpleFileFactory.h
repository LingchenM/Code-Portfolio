#pragma once
#include "AbstractFileFactory.h"
#include "string"
class SimpleFileFactory : public AbstractFileFactory{
public:
    AbstractFile* createFile(string);
};