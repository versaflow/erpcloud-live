
    function getContacts() {
        var contacts1 = [];
        for (var i = 0; i < messageDataSourceNew.length; i++) {
            addContacts(messageDataSourceNew[i], 'Email', 'text', contacts1);
        }
        return contacts1;
    }
    function addContacts(messageData, mailId, text, contacts) {
        var fieldId = 'MailId';
        var contacts1 = [];
        var contactData = {};
        if (messageData[mailId]) {
            if (messageData[mailId] instanceof Array) {
                var mailIdList = messageData[mailId];
                var contactsList = messageData[text];
                for (var j = 0; j < mailIdList.length; j++) {
                    contactData = {};
                    if (!istextExist(contacts, mailIdList[j])) {
                        fieldId = 'MailId';
                        contactData[fieldId] = mailIdList[j];
                        fieldId = 'text';
                        contactData[fieldId] = contactsList[j];
                        contactData.Image = messageData.Image;
                        contacts.push(contactData);
                    }
                }
            }
            else {
                if (!istextExist(contacts, messageData[mailId].toString())) {
                    contactData[fieldId] = messageData[mailId];
                    mailId = 'text';
                    contactData[mailId] = messageData[text];
                    contactData.Image = messageData.Image;
                    contacts.push(contactData);
                }
            }
        }
        return contacts;
    }
    function istextExist(contacts, text) {
        var key = 'MailId';
        for (var i = 0; i < contacts.length; i++) {
            if (contacts[i][key]) {
                if (contacts[i][key].toString() === text) {
                    return true;
                }
            }
        }
        return false;
    }

