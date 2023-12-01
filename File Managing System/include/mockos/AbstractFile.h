#pragma once
#include <string>
#include <vector>
#include "AbstractFileVisitor.h"

using namespace std;
class TextFile;
class ImageFile;

class AbstractFile{
public:
//    virtual void read() = 0;
    virtual vector<char> read() = 0;
    virtual int write(vector<char>) = 0;
    virtual int append(vector<char>) = 0;
    virtual unsigned int getSize() = 0;
    virtual string getName() = 0;
    virtual void accept(AbstractFileVisitor*) = 0;
    virtual AbstractFile* clone(string s) = 0;
    virtual ~AbstractFile() = default;
};

enum sucfail {
    success = 0,
    size_mismatch = 1,
    Found_character_besides_X_and_space = 2,
    append_is_not_supported = 3,
    File_already_exists = 4,
    File_nonexists = 5,
    File_is_open = 6,
    File_is_not_open = 7,
    Wrong_type = 8,
    wrongPass = 9,
    failInsert = 10,
    quit = 11,
    incorrectArg = 12,
    failCreateFile = 13,
    failAddFile = 14,
    noFileProvided = 15,
    failCreateFileWithPass = 16, 
    cannotCopy = 17,
    cannotAppend = 18
};