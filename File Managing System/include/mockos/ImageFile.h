#pragma once
#include <string>
#include <vector>
#include "AbstractFile.h"

using namespace std;

class ImageFile : public AbstractFile{
private:
    string name;
    vector<char> contents;
    char size;
public:
    ImageFile(string);
    unsigned int getSize();
    string getName();
    int write(vector<char>);
    int append(vector<char>);
    vector<char> read();
    void accept(AbstractFileVisitor*);
    AbstractFile* clone(string s);

//    void read() ;
};
