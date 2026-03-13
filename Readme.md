# Edu-sharing PHP Library

## Usage Scenarios
This library is intended for third party systems (e.g., LMS, CMS) to interconnect with edu-sharing to embed edu-sharing materials into their pages.

## Examples
This repository includes a minimal example for using the library. 
For a more involved example, please check out the Edu-Sharing Moodle Plugins:

- [Activity Plugin](https://github.com/edu-sharing/moodle-mod_edusharing) wraps this library to facilitate communication with the repository (class EdusharingService)
- [Tiny MCE Plugin](https://github.com/edu-sharing/moodle-tiny_edusharing) handles embedding Edu-Sharing objects in a wysiwyg editor.
- [Filter Plugin](https://github.com/edu-sharing/moodle-filter_edusharing) handles displaying embedded ES-Objects.

### Run example

Call 

```bash
docker compose build && docker compose up
```
and open in your browser: http://localhost:8080/example

In your storage, check the new folder `data`. There you'll find the `sample-app.properties.xml` file you must upload to your repository (see Pre-Requisites).

## Pre-Requisites
Every third-party system will need to be registered in edu-sharing first.
edu-sharing 9.0 or greater must be used to make use of this library.

To register systems, log in to your edu-sharing as an administrator, switch to Admin-Tools → Remote-Systems

When using Edu-Sharing >10.0 with the new rendering service, you need to add your local testing host to the list of allowed hosts:

1. Login to Edu-Sharing as admin
2. Admin-Tools
3. Advanced System Configuration
4. homeApplication.properties.xml edit
5. Scroll to "allow_origin" and add your local host (e.g., http://localhost:8080, more than one allowed as a comma-separated list)
6. Apply
7. Wait for the changes to take effect (a job runs every 5 minutes to sync allowed origins with the rendering service)

This step is not required in production systems.

## Composer Usage (Beta)
If you already use composer, you can fetch this library as a composer dependency

`composer require edu-sharing/auth-plugin 10.0.x-dev`.

You can access all classes in the `EduSharing` namespace. 

Find out more about the package and available versions here: https://packagist.org/packages/edu-sharing/auth-plugin

## How to Register Your App?
The registration is handled by providing a public key to the repository (via an XML file).

You can create such a registration file by calling
`php example/example.php`. It will create a `private.key` file (make sure to safely store this file and never expose it to clients!).
The generated `properties.xml` file can then be used to register the app in edu-sharing. (see [Pre-Requisites](#pre-requisites))
(This is not required when using docker, it will be executed automatically)

## Basic Workflows & Features

There are two common use cases:

### 1. Logging in and selecting an object to embed
For this workflow, you first need to call `getTicketForUser` including the `userid` to fetch a ticket for your authenticated user. Since your app is registered via the public key, we will trust your request and return you a ticket (similar to a user session).

After you have a ticket, you will navigate the user to the edu-sharing UI so that they can select an element.
`<base-url>/components/search?ticket=<ticket>&reurl=IFRAME`

When the user has picked an element, you will receive the particular element via JavaScript:
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

Check the `docker-compose.yml` file for the `BASE_URL` variables. Then use `docker compose build && docker compose up -d` inside the `example` folder and open http://localhost:8080/example.  

### 2. Rendering / displaying a previously embedded object
If you previously generated a usage for an object, you can fetch it for displaying / rendering.
Simply call `getNodeByUsage` including the usage data you received previously.

You'll get the full node object (see the REST specification) as well as a ready-to-embed HTML snipped (`detailsSnippet`).


#### 2.1 Rendering / displaying with Edu-Sharing 10.0

**UPDATE FOR EDU-SHARING 10.0**
Edusharing 10.0 uses a new Rendering Service which no longer provides a raw HTML snippet for embedding. Instead, it makes use of a web component for displaying the content.

##### 2.1.1 Configuring the Edu-Sharing REST base url

The web component calls two public endpoints of the Edu-Sharing REST API for which it needs the base path. Please configure it thus:

```
window.__env = {
    EDU_SHARING_API_URL: YOUR_REPO_HOST/edu-sharing + '/rest'
};
```

##### 2.1.2 Adding the web component to your app

You can either add the web component to your app by using npm ([Link](https://www.npmjs.com/package/ngx-edu-sharing-rendering-web-component)) or by using the assets provided by the repository. The latter option is recommended as it ensures smooth updates and is used in the following examples.
In any case two assets need to be added: ```main.js``` and ```styles.css```:

```
const script = document.createElement('script');
script.src = 'YOUR_REPO_HOST/edu-sharing/web-components/rendering-service/main.js';
script.type = 'module';
document.head.appendChild(script);

const link = document.createElement('link');
link.rel = 'stylesheet';
link.href = 'YOUR_REPO_HOST/edu-sharing/web-components/rendering-service/styles.css';
link.type = 'text/css';
document.head.appendChild(link);
```
or
```
<!-- Module script -->
<script type="module" src="YOUR_REPO_HOST/edu-sharing/web-components/rendering-service/main.js"></script>

<!-- Stylesheet -->
<link rel="stylesheet" type="text/css" href="YOUR_REPO_HOST/edu-sharing/web-components/rendering-service/styles.css">

```
By default, the web component is bundled using ESM. If you need AMD (for example for apps using require.js) simply change ```rendering-service``` to ```rendering-service-amd``` in the links.

**NOTE**:
When using the AMD bundle, you need to tell webpack where to look for assets and chunks:

```    
window.__EDUSHARING_PUBLIC_PATH__ = `${repoUrl}/web-components/rendering-service-amd/`;
```

##### 2.1.3 Enabling the service worker

For session handling, the new rendering Service uses a cookie. If, for some reason, the cookie is rejected by the browser, an authentication header is managed and set by a service worker as a backup strategy. You need to add this service worker to your app.
As it has to be served by your app using specific headers, you have to create your own endpoint:

```
header('Content-Type: text/javascript');
header('Service-Worker-Allowed: /');
header('Cache-Control: no-cache, no-store, must-revalidate');
```

For an example endpoint see: [Moodle Service Worker Endpoint](https://github.com/edu-sharing/moodle-filter_edusharing/blob/main/getServiceWorker.php)
As with the web component itself you can either use the npm package and serve the "edu-service-worker.js" from the node modules folder or create a proxy for the service worker hosted by the repository (either ```YOUR_REPO_HOST/edu-sharing/web-components/rendering-service/edu-service-worker.js``` or ```YOUR_REPO_HOST/edu-sharing/web-components/rendering-service-amd/edu-service-worker.js```)

With the endpoint/proxy in place, you can now add the service worker like this

```JS
const serviceWorkerScript = `PATH/TO/YOUR/SERVICEWORKERENDPOINT/getServiceWorker.php`;
if ('serviceWorker' in navigator && !navigator.serviceWorker.ready) {
    await navigator.serviceWorker.register(serviceWorkerScript, {
        scope: '/'
    });
    await navigator.serviceWorker.ready;
}
```
**Important:**
If, for some reason, you use a proxy for communication with the Rendering Service 2 API, please make sure it also forwards the "Authentication-Info" header. It is necessary for the service worker to function.

**NOTE:**
When testing locally (using non-secure http), you need to allow service workers in your browser:
- Chrome: chrome://flags → Insecure origins treated as secure → Add your origin (e.g., http://localhost:8080) → Enable 
- Firefox: dev console → Settings → Check "Enable service workers over http (when toolbox is open)"

##### 2.1.4 Instantiation of the web component

You can now add the web component to the DOM using JavaScript and set its inputs. Before doing so, you need to fetch the required data from the repository using this library:

```
$result = $nodeHelper->getSecuredNodeByUsage($YOUR_USAGE);
```
Furthermore, you will need the Rendering Service base url which can be obtained thus:
```
$about = $nodehelper->base->getAbout();
if (isset ($about['renderingService2']['url'])) {
    return $about['renderingService2']['url'];
}
throw new Exception('Rendering Service 2 is not configured');
```

With this data you can now add the custom element to the DOM.
```
const renderComponent = document.createElement('edu-sharing-render'); // From the call to getSecuredNode
renderComponent.encoded_node = result.securedNode; // From the call to getSecuredNode
renderComponent.signature = result.signature; // From the call to getSecuredNode
renderComponent.jwt = result.jwt; // From the call to getSecuredNode
renderComponent.render_url = renderingBaseUrl; // from getAbout()
renderComponent.service_worker_url = ""; // Leave as is
renderComponent.activate_service_worker = false; // Leave as is
renderComponent.assets_url = repoUrl + '/web-components/rendering-service/assets'; // Path to the assets of the web component
renderComponent.resource_url = resourceUrl; // See section 2.2
wrapper.innerHTML = "";
wrapper.appendChild(renderComponent);
```

#### 2.2 Content + Download Linking, Preview
Since the object you've received is probably not publicly available, you need to generate specific urls to access it via the current usage.

You'll need a dedicated endpoint in your application which verifies access of the current user and then redirects them to edu-sharing.

When initializing the library, configure the path where this endpoint will be available in your application like


```php
$nodeHelper = new EduSharingNodeHelper($base,
    new EduSharingNodeHelperConfig(
        new UrlHandling(true, 'my/api/redirect/endpoint')
    )
);
```

This endpoint should then verify your user's permissions and call the redirect method of the library:
```php
        $about = $nodeHelper->base->getAbout();
        $useRendering2 =  isset ($about['renderingService2']['url']);
        $url = $nodeHelper->getRedirectUrl(
            mode: $_GET['mode'],
            usage: new Usage(
                $_GET['nodeId'],
                $_GET['nodeVersion'] ?? null,
                $_GET['containerId'],
                $_GET['resourceId'],
                $_GET['usageId'],
            ),
            rendering2: $useRendering2
        );
        header("Location: $url");
```
This method will create a signed url with a current timestamp which will then give the user temporary access to the given object.

The `urls` section you got returned from `getNodeByUsage` already targets the endpoint url you specified.

For preview images, there is the method `getPreview` which will return the binary data of the image. 

## FAQ

### What's a usage?
A usage is both information about and access permission for a particular element.

A usage can be created by a registered app. The usage will later allow this app to fetch the given element at any given time without additional permissions.

### Do I need a ticket / authenticated user before fetching an element via the usage information?
No. The element only needs to have a usage. The usage will allow access for this element for your app.
edu-sharing will "trust" your application to only fetch elements for usages that you made sure the current user should have access to (e.g., a particular page or course).

### Can I create a usage without a ticket / authenticated user?
No. To create a usage, we first need to make sure that the user who wants to generate it has appropriate permissions for the given element. Thus, we need a ticket to confirm the user state. Also, the user information will be stored on the usage.

### How can I find out if an element already has usages or not?
In edu-sharing, with appropriate permissions, right-click and choose "Invite." In the section "Invited" you'll also see the list of usages and may also "revoke" usages for the particular element.

### Do I need usages for public elements?
In theory: no. Since the element is accessible to everyone, the usage is not required from a permission standpoint.

However, we use the usage for tracking/statistics purposes. Also, the node may get private at some point in the future which would break any remote embeddings. Thus, you should always create a usage.  

### Object Versions
You can use a specific node version by using the version parameter when fetching the content. However, the version is only supported for nodes which DO NOT have the aspect `ccm:published` and `ccm:collection_io_reference`.
For nodes with one of these aspects, you may not send a specific version; otherwise the fetching will fail.
You can find out all aspects of the user-selected node in the `node.aspects` array.

## Advanced Usage

### Custom Curl Handler
In case the system you're working with already provides a curl implementation (e.g., for global configuration of proxies, redirects or other features), you might want to route all requests from this library through the existing implementation.

You can attach a custom curl handler in this case. Please note that you must do this directly after instantiating the base library; otherwise some requests might already have been sent.

```php
$base->registerCurlHandler(new class extends CurlHandler {

    public function handleCurlRequest(string $url, array $curlOptions): CurlResult
    {
       return new CurlResult('', 0, []);
    }
});
```
Take a look at the `curl.php` file for more details and an example.


# Notes for Manual/Custom implementation

If you can't use the library or need to implement the edu-sharing integration, i.e., in another programming language, here are some general tips.

## Adding the signing headers
For each request you will make to our api, you will need to add the following headers:

```
X-Edu-App-Id: <your-app-id>,
X-Edu-App-Signed: <signed-payload-including-the-timestamp>,
X-Edu-App-Sig: <signature>,
X-Edu-App-Ts: <unix-timestamp-in-ms>,
```

The signature must be generated using your private key (where the public key is known to the edu-sharing repository), in PHP, it will be done like
```php
$privateKeyId = openssl_get_privatekey($privateKey);
openssl_sign($toSign, $signature, $privateKeyId);
return base64_encode($signature);
```

in Java, it would be:
```java
Signature dsa = Signature.getInstance(ALGORITHM_SIGNATURE);
dsa.initSign(privateKey);
dsa.update(data.getBytes());
byte[] signature = dsa.sign();
return new Base64().encode(signature);
```

## Fetching ticket

Fetching a ticket (which you can use to authenticate as the user later one) is done by calling 

`GET /rest/authentication/v1/appauth/<username>`

Please remember to attach your signature headers as described before.

## Creating Usage

Creating a usage is done by calling 


`POST /rest/usage/v1/usages/repository/-home-`

including the (JSON) payload

```json
{
  "appId": "<your-app-id>",
  "courseId": "<container-id>",
  "resourceId": "<resource-id>",
  "nodeId": "<node-id>",
  "nodeVersion": "<node-version>"
}
```

and the following header including the previously fetched user ticket

`Authorization: EDU-TICKET <ticket>`

To learn more about the individual data of this payload, please refer to the code docs of the `createUsage` method in this library.


## Fetching an element by usage

Fetching the previously generated usage is done by calling

`GET /rest/rendering/v1/details/-home-/<usage-node-id>`

and adding the following HTTP headers:

```
X-Edu-Usage-Node-Id: <usage-node-id>
X-Edu-Usage-Course-Id: <usage-container-id>
X-Edu-Usage-Resource-Id: <usage-resource-id>
X-Edu-User-Id: <user-id> # optional
```
