#pragma once
#include <string>
#include <iostream>
#include <vector>
#include "AbstractFile.h"

using namespace std;
class TextFile : public AbstractFile{
private:
    vector<char> contents;
    string name;
public:
    TextFile(string);
    unsigned int getSize() override;
    string getName() override;
    int write(vector<char>);
    int append(vector<char>);
    vector<char> read();
    void accept(AbstractFileVisitor*);
    AbstractFile* clone(string s);

//    void read() override;
};