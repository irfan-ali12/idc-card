// Find the part of the code that generates the QR code.
// It will look similar to this.
// Change the width and height from a larger value (e.g., 100 or 128) to 60.

if (document.getElementById('w_qr')) {
  new QRCode(document.getElementById('w_qr'), {
    text: 'Your QR code data here',
    width: 60,
    height: 60,
    colorDark: '#000000',
    colorLight: '#ffffff',
    correctLevel: QRCode.CorrectLevel.H
  });
}