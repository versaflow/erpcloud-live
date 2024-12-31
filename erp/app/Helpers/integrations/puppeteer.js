const fs = require('fs');
const path = require('path');
const events = require('events');
const puppeteer = require('puppeteer');
const args = process.argv.slice(2);
const token = args[0];
const export_format = args[1];

let eventEmitter = new events.EventEmitter();

const directoryPath = "./storage/"; /* A path to the storage of exported files */

((directoryPath) => {
  fs.mkdir(path.resolve(path.resolve(),
    directoryPath.replace(/^\.*\/|\/?[^\/]+\.[a-z]+|\/$/g, '')), { recursive: true }, error => {
      if (error) console.error(error);
    });
})(directoryPath); /* Creating a storage folder for exported files (if such a folder doesn't exist yet) */

(async () => {

  eventEmitter.once('reportcomplete', () => {

    /*
      All changes should be made within this function.
 
      Available methods:
      - setReport (https://www.flexmonster.com/api/setreport/)
      - exportTo (https://www.flexmonster.com/api/exportto/)
 
      The exportTo method takes two parameters: type and params.
      Callback function will be ignored.
      Possible destination types:
      - plain (the file will be saved by the path defined as a value of the "directoryPath" variable)
      - server (the file will be exported to the server)
 
      Available events (use "eventEmitter" to manage events):
      - ready (https://www.flexmonster.com/api/ready/)
      - reportcomplete (https://www.flexmonster.com/api/reportcomplete/)
      - exportcomplete (https://www.flexmonster.com/api/exportcomplete/)
 
      Additional methods and events can be added using the template.
    */

    eventEmitter.once('reportcomplete', () => { /* Exporting when the report is ready */
      console.log('export start');
      console.log(token);
      console.log(export_format);
      if(export_format == 'csv'){
      var csv_params = {
        filename : 'export.csv', 
        destinationType : 'server',
        url : 'https://portal.telecloud.co.za/flexmonster_export_save?file_ext=csv&token='+token,
      };
      exportTo("csv",csv_params);
      }
      
      if(export_format == 'html'){
      console.log(export_format);
      var html_params = {
        filename : 'export.html', 
        destinationType : 'server',
        url : 'https://portal.telecloud.co.za/flexmonster_export_save?file_ext=html&token='+token,
      };
      exportTo("html",html_params);
      }
      
      if(export_format == 'png'){
      var image_params = {
        filename : 'export.png', 
        destinationType : 'server',
        url : 'https://portal.telecloud.co.za/flexmonster_export_save?file_ext=png&token='+token,
      };
      exportTo("image",image_params);
      }
      
      if(export_format == 'xlsx'){
      var excel_params = {
        filename : 'export.xlsx', 
        destinationType : 'server',
        url : 'https://portal.telecloud.co.za/flexmonster_export_save?file_ext=xlsx&token='+token,
      };
      exportTo("excel",excel_params);
      }
      
      if(export_format == 'pdf'){
      var pdf_params = {
        filename : 'export.pdf', 
        destinationType : 'server',
        url : 'https://portal.telecloud.co.za/flexmonster_export_save?file_ext=pdf&token='+token,
      };
      exportTo("pdf",pdf_params);
      }
      
    });

    let exportCount = 0;
    eventEmitter.on('exportcomplete', () => {
      console.log('exportcomplete');
      exportCount++;
      console.log(exportCount);
      if (exportCount == 1) browser.close(); /* Closing the browser when all the exports are complete */
    });

    

  });

  const browser = await puppeteer.launch({
    executablePath: '/usr/bin/chromium-browser',
    headless: true,
    args: [
      "--no-sandbox",
      "--start-fullscreen",
    ],
  }); /* Launching the headless browser */
  const page = await browser.newPage(); /* Creating a new page */
  await page.setViewport({ width: 1600, height: 1600});

  

  /* This code is responsible for the export itself. It supports five export formats: 
     .html, .xlsx, .pdf, .csv, and .png. */
     
  function exportTo(type, params) {
    console.log(type);
    console.log(params);
    page.evaluate((type, params) => {
    console.log(type);
    console.log(params);
       console.log('export eval start');
    
      type = type.toLowerCase();
     
      flexmonster.exportTo(type, params);
    }, type, params);
  }
  
  
  /* write to local server . */
     /*
  function exportTo(type, params) {
    page.evaluate((type, params) => {
      type = type.toLowerCase();
      if (params) {
        if (params.destinationType != "plain" && params.destinationType != "server")
          params.destinationType = "plain";
      }
      else params = { destinationType: "plain" };
      if (!params.filename) params.filename = "pivot";
      flexmonster.exportTo(type, params, (result) => {
        switch (type) {
          case "pdf":
            result.data = result.data.output();
            break;
          case "excel":
            result.data = Array.from(result.data);
            break;
          case "image":
            result.data = result.data.toDataURL();
            break;
        }
        exportHandler(result);
      });
    }, type, params);
  }

  await page.exposeFunction('exportHandler', (result) => {
    switch (result.type) {
      case "excel":
        result.data = Buffer.from(result.data);
        result.type = "xlsx";
        break;
      case "image":
        result.data = Buffer.from(result.data.replace(/^data:image\/\w+;base64,/, ""), 'base64');
        result.type = "png";
        break;
    }
    fs.writeFile(`${directoryPath}${result.filename}.${result.type}`, result.data, result.type == "pdf" ? "ascii" : "utf-8", error => {
      if (error) console.log(error);
    });
  });
*/

  /* This code adds functions to emit ready, reportcomplete, and exportcomplete events for the browser 
     when called. This approach allows us to handle the component's events in the browser's scope. */
  await page.exposeFunction('onReady', () => {
    eventEmitter.emit('ready')
  });
  await page.exposeFunction('onReportComplete', () => {
    eventEmitter.emit('reportcomplete')
  });
  await page.exposeFunction('onExportComplete', () => {
    eventEmitter.emit('exportcomplete')
  });
  
  
  /*subscribe to all console messages*/
  page
    .on('console', message =>
      console.log(`${message.type().substr(0, 3).toUpperCase()} ${message.text()}`))
    .on('pageerror', ({ message }) => console.log(message))
    .on('response', response =>
      console.log(`${response.status()} ${response.url()}`))
    .on('requestfailed', request =>
      console.log(`${request.failure().errorText} ${request.url()}`))

  /*  Reading the file with the component and setting it as the browser page's contents */
  
  await page.setContent(fs.readFileSync('export.html', 'utf8'));
  

  /* This code runs in the page's scope, subscribing the browser window to the component's ready, 
     reportcomplete, and exportcomplete events */
  await page.evaluate((token) => {
    console.log(token);
    $(document).ready(function(){
        new ErpReports(token);
    });
    window.addEventListener('ready', () => window.onReady());
    window.addEventListener('reportcomplete', () => window.onReportComplete());
    window.addEventListener('exportcomplete', () => window.onExportComplete());
  },token);

})();