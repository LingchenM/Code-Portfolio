#include "AbstractFileVisitor.h"

class MetadataDisplayVisitor:public AbstractFileVisitor{
    void visit_TextFile(TextFile*);
    void visit_ImageFile(ImageFile*);
};