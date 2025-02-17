
const FnbApi = require('fnb-api').Api;

(async () => {
        const args = process.argv;
        //console.log(args);
        var fnb_username = args[2];
        var fnb_passwd = args[3];
        var fnb_account_no = args[4];
        
       // console.log(fnb_username);
        
       // console.log(fnb_passwd);
        
        //console.log(fnb_account_no);
    
        const customUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
        
        const api = new FnbApi({
            username: fnb_username,
            password: fnb_passwd,
            headless: "new",
            puppeteerOptions:{ args: ['--no-sandbox', '--user-agent=${customUserAgent}']},
        });
        
        const accounts = await api.accounts.get();
        
        // console.log(accounts);
        
        for (const fnb_account of accounts) {
         //         console.log(fnb_account);
            if(fnb_account_no == fnb_account.accountNumber){
                // console.log('account assigned');
                // console.log(fnb_account);
                var account = fnb_account;
            }
        }
        
       // console.log('for loop complete');
        //console.log(account);
        
        
       // const detailedBalance = await account.detailedBalance();
        // console.log(detailedBalance);
        
        //var transactions = await account.transactions();
        //console.log(transactions[0]);
        
        var transactions = false;
        if(account){
             var transactions = await account.transactions();
             
        }else{
            return false;    
        }
        // console.log(transactions);
        
        console.log(JSON.stringify(transactions));
        await api.close();
        
      
})();


