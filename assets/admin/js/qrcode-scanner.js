     document.addEventListener('DOMContentLoaded', (event) => {

         const resultContainer = document.getElementById('result');
         let lastScanTime = 0;
         const scanCooldown = 3000; // Cooldown in milliseconds (3 seconds)
         let search = $('input[name="search"]');
         search.focus()

         // This function is called when a QR code is successfully scanned
         function onScanSuccess(decodedText, decodedResult) {
             const currentTime = new Date().getTime();

             // Simple cooldown to prevent multiple submissions for the same scan
             if (currentTime - lastScanTime < scanCooldown) {
                 return;
             }
             lastScanTime = currentTime;

             resultContainer.innerHTML = `Scanning... please wait.`;
             resultContainer.className = '';

             // Send the decoded text to the Laravel backend using fetch API
             // window.location.href = decodedText;
             console.log('open decodedText', decodedText)
             search.focus()
             search.val(decodedText)
             search.parent().parent().submit()
         }

         // This function is called when a scan fails (e.g., no QR code found)
         function onScanFailure(error) {
             //   console.error(error)
             // We can ignore this, as it fires continuously when no QR code is in view.
             //  console.warn(`Code scan error = ${error}`);
         }


         let html5QrcodeScanner;

         $('#btn-evouch-scanner').on('click', function () {
             $('#qrCodeScannerModal').modal('show')

             setTimeout(() => {
                 // Create a new scanner instance
                 html5QrcodeScanner = new Html5QrcodeScanner(
                     "reader", // ID of the element to render the scanner
                     {
                         fps: 10,
                         qrbox: {
                             width: '250',
                             height: '250'
                         }
                     },
                     /* verbose= */
                     false);
                 // Render the scanner
                 html5QrcodeScanner.render(onScanSuccess, onScanFailure);
             }, 500);
         })

         document.getElementById('qrCodeScannerModal').addEventListener('hidden.bs.modal', () => {
             if (html5QrcodeScanner) {
                 html5QrcodeScanner.clear()
                     .then(() => {
                         console.log("Camera stopped.");
                     })
                     .catch(err => console.log("Error stopping camera:", err));
             }
         });
     });