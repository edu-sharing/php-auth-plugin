# edu-sharing php library

## Usage scenarios
This library is intented for 3rd party systems (e.g. LMS, CMS) to interconnect with edu-sharing in order to embed edu-sharing materials into their pages.

## Pre-Requisites
Every 3rd party system will need to registered in edu-sharing first.

To register systems, login in your edu-sharing as an administrator, switch to Admin-Tools -> Remote-Systems

### How to register your app?
The registration is handled by providing a public key to the repository (via an xml file).

You can create such a registration file by calling
`php example/example.php`. It will create a private.key file (make sure to savely store this file and never expose it to clients!).
The generated properties.xml file can than be used to register the app in edu-sharing. (see Pre-Requisites)

### Basic workflow & features

There are 2 common use cases:

#### 1. Logging in and selecting an object to embed
For this workflow, you first need to call `getTicketForUser` including the userid to fetch a ticket for your authenticated user. Since your app is registered via the public key, we will trust your request and return you a ticket (similar to a user session).

After you have an ticket, you will navigate the user to the edu-sharing UI so that he can select an element.
`<base-url>/components/search?ticket=<ticket>&reurl=IFRAME`

When the user picked an element, you will receive the particular element via javascript:
```javascript
window.addEventListener('message', receiveMessage, false);
function receiveMessage(event){
    if(event.data.event === 'APPLY_NODE'){ // Event Name hier festlegen
        console.log(event.data.data);
    }
}
```

You now need to use the method `createUsage` in order to create a persistent usage for this element.

Persist the data you receive from this method to display it later.

A full working example can be viewed if you open the `example/index.html` (you need to register your app first, see above)

### 2. Rendering / Displaying a previously embeded object
If you previously generated an usage for an object, you can fetch it for displaying / rendering
Simply call `getNodeByUsage` including the usage data you received previously.

You'll get the full node object (see the REST specification) as well as a "ready to embed" html snipped (`detailsSnippet`)


## FAQ

### What's an usage?
An usage is both an information and access permission for a particular element.

A usage can be created by a registered app. The usage will later allow this app to fetch the given element at any given time without additional permissions.

### Do I need a Ticket / signed in user before fetching an element via the usage information?
No. The element only needs to have an usage. The usage will allow access for this element for your app.
edu-sharing will "trust" your application that you only fetch elements for usages where you made sure that the current user should have access to (e.g. a particular page or course).

### Can I create an usage without a Ticket / signed in user
No. In order to create an usage, we first need to make sure that the user who wants to generate it has appropriate permissions for the given element. Thus, we need a ticket to confirm the user state. Also, the user information will be stored on the usage.

### How can I find out if an element already has usages or not?
In edu-sharing, with appropriate permissions, right click and choose "Invite". In the section "Invited" you'll also see the list of usages and may also "revoke" usages for the particular element.

### Do I need usages for public elements
In theory - no, since the element is accessible for everyone, the usage is not required from a permission standpoint.

However, we use the usage for tracking/statistics purposes. Also, the node may get private at some point in the future which would break any remote embeddings. Thus, you should always create an usage.  