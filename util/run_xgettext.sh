#!/bin/bash

cd $(dirname $0)

OPTS=

if [ -e "$1" ]
then
	OPTS="-j -o $1"
fi

KEYWORDS="-k -kt -ktt:1,2"

find .. -name "*.php" | xargs xgettext $KEYWORDS $OPTS --from-code=UTF-8

F9KVERSION=$(sed -n "s/.*'FRIENDIKA_VERSION'.*'\([0-9.]*\)'.*/\1/p" ../boot.php); 


sed -i "s/SOME DESCRIPTIVE TITLE./FRIENDIKA Distribuited Social Network/g" messages.po
sed -i "s/YEAR THE PACKAGE'S COPYRIGHT HOLDER/2010, 2011 Mike Macgirvin/g" messages.po
sed -i "s/FIRST AUTHOR <EMAIL@ADDRESS>, YEAR./Mike Macgirvin, 2010/g" messages.po
sed -i "s/PACKAGE VERSION/$F9KVERSION/g" messages.po
sed -i "s/PACKAGE/Friendika/g" messages.po
sed -i "s/CHARSET/UTF-8/g" messages.po
sed -i "s|#: \.\./|#: ../../|g" messages.po

