#pragma once
#include"AbstractCommand.h"
#include"AbstractParsingStrategy.h"
#include<vector>

class TCParsingStrategy : public AbstractParsingStrategy {
public:
	vector<string>parse(string s) override;
};