# Edu-sharing PHP Library

## Usage Scenarios
This library is intended for 3rd party systems (e.g. LMS, CMS) to interconnect with edu-sharing in order to embed edu-sharing materials into their pages.

## Pre-Requisites
Every 3rd party system will need to be registered in edu-sharing first.
edu-sharing 6.0 or greater must be used in order to make use of this library.

To register systems, log in to your edu-sharing as an administrator, switch to Admin-Tools -> Remote-Systems

## How to Register Your App?
The registration is handled by providing a public key to the repository (via an XML file).

You can create such a registration file by calling
`php example/example.php`. It will create a `private.key` file (make sure to safely store this file and never expose it to clients!).
The generated `properties.xml` file can then be used to register the app in edu-sharing. (see [Pre-Requisites](#pre-requisites))

## Basic Workflow & Features

There are 2 common use cases:

### 1. Logging in and selecting an object to embed
For this workflow, you first need to call `getTicketForUser` including the `userid` to fetch a ticket for your authenticated user. Since your app is registered via the public key, we will trust your request and return you a ticket (similar to a user session).

After you have a ticket, you will navigate the user to the edu-sharing UI so that they can select an element.
`<base-url>/components/search?ticket=<ticket>&reurl=IFRAME`

When the user picked an element, you will receive the particular element via JavaScript:
```js
window.addEventListener('message', receiveMessage, false);
function receiveMessage(event) {
    if (event.data.event === 'APPLY_NODE') {
        console.log(event.data.data);
    }
}
```

You now need to use the method `createUsage` in order to create a persistent usage for this element.

Persist the data you receive from this method to display it later.

A full working example is given in `example/index.html` (you need to register your app first, see above)

Check the `docker-compose.yml` file for the `BASE_URL` variables. Then use `docker compose up -d` inside the `example` folder and open http://localhost:8080/example.  

### 2. Rendering / displaying a previously embedded object
If you previously generated a usage for an object, you can fetch it for displaying / rendering.
Simply call `getNodeByUsage` including the usage data you received previously.

You'll get the full node object (see the REST specification) as well as a ready-to-embed HTML snipped (`detailsSnippet`).


## FAQ

### What's a usage?
A usage is both information about and access permission for a particular element.

A usage can be created by a registered app. The usage will later allow this app to fetch the given element at any given time without additional permissions.

### Do I need a ticket / signed in user before fetching an element via the usage information?
No. The element only needs to have a usage. The usage will allow access for this element for your app.
edu-sharing will "trust" your application to only fetch elements for usages that you made sure the current user should have access to (e.g. a particular page or course).

### Can I create a usage without a ticket / signed in user?
No. In order to create a usage, we first need to make sure that the user who wants to generate it has appropriate permissions for the given element. Thus, we need a ticket to confirm the user state. Also, the user information will be stored on the usage.

### How can I find out if an element already has usages or not?
In edu-sharing, with appropriate permissions, right click and choose "Invite". In the section "Invited" you'll also see the list of usages and may also "revoke" usages for the particular element.

### Do I need usages for public elements?
In theory: no. Since the element is accessible for everyone, the usage is not required from a permission standpoint.

However, we use the usage for tracking/statistics purposes. Also, the node may get private at some point in the future which would break any remote embeddings. Thus, you should always create a usage.  

## Advanced Usage

### Custom Curl Handler
In case the system you're working with already provides a curl implementation (e.g. for global configuration of proxies, redirects or other features), you might want to route all requests from this library through the existing implementation.

You can attach a custom curl handler in this case. Please note that you must do this directly after instantiating the base library, otherwise some requests might already have been sent.

```php
$base->registerCurlHandler(new class extends CurlHandler {

    public function handleCurlRequest(string $url, array $curlOptions): CurlResult
    {
       return new CurlResult('', 0, []);
    }
});
```
Take a look at the `curl.php` file for more details and an example.