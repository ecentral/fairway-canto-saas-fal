# PHP client for Canto API

## Example usage

```php
use Ecentral\CantoSaasApiClient\ClientOptions;
use Ecentral\CantoSaasApiClient\Client;
use Ecentral\CantoSaasApiClient\Http\LibraryTree\GetTreeRequest;

$clientOptions = new ClientOptions([
    'cantoName' => 'my-canto-name',
    'cantoDomain' => 'canto.de',
    'appId' => '123456789',
    'appSecret' => 'my-app-secret',
]);
$client = new Client($clientOptions);
$accessToken = $client->authorizeWithClientCredentials('my-user@email.com')
                      ->getAccessToken();
$client->setAccessToken($accessToken);
$allFolders = $client->libraryTree()
                     ->getTree(new GetTreeRequest())
                     ->getResults();
```
