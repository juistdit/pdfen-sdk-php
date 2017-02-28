# An SDK in PHP for PDFen (In-development)

An SDK allowing easy conversion from any supported format to PDF using the PDFen.com API.
## Installing
This package can be installed using Composer. TODO.

## Usage

After installing PDFen SDK using composer, the first step is to create a PDFen SDK object and creating a session using your credentials:

```php
$sdk = new PDFen\Sdk;
$session = $sdk->login("account@example.com", "123456789IsNotASafePassword");
```