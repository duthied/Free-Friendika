Circles and Privacy
==================

* [Home](help)


Circles are merely collections of friends.
But Friendica uses these to unlock some very powerful features.

**Setting Up Circles**

To create a circle, visit your Friendica "Contacts" page and select "Create a new circle".
Give the circle a name.

This brings you to a page where you can select the circle members.

You will have two boxes on this page.
The top box is the roster of current circle members.
Below that is another box containing all of your friends who are *not* members of the circle.

If you click on a photo of a person who isn't in the circle, they will be put into the circle.
If you click on a photo of a person who is in the circle, they will be removed from it.

**Access Control**

Once you have created a circle, you may use it in any access control list.
This is the little lock icon beneath the status update box on your home page.
If you click this, you can select who can see and who can *not* see the post you are about to make...
These can be individual people or circles.

On your "Network" page, you will find posts and conversations from everybody in your network.
You may select an individual circle on this page to show conversations pertaining only to members of that circle.

But wait, there's more...

If you look carefully when visiting a circle from your Network page, the lock icon under the status update box has an exclamation mark next to it.
This is meant to draw attention to that lock.
Click the lock.
You will see that since you are only viewing a certain circle of people, your status updates while on that screen default to only being seen by that same circle of people.
This is how you keep your future employers from seeing what you write to your drinking buddies.
You can override this setting, but this makes it easy to separate your conversations into different friend circles.

**Default Post Privacy**

By default, Friendica assumes that you want all of your posts to be private.
Therefore, when you sign up, Friendica creates a circle for you that it will automatically add all of your contacts to.
All of your posts are restricted to that circle by default.

Note that this behaviour can be overridden by your site admin, in which case your posts will be "public" (i.e., visible to the entire Internet) by default.

If you want your posts to be "public" by default, you can change your default post permissions on your Settings page.
You also have the option to change which circles you post to by default or which circle your new contacts get placed into by default.

**Privacy Concerns To Be Aware Of**

These private conversations work best when your friends are Friendica members.
We know who else can see the conversations - nobody, *unless* your friends cut and paste the messages and send them to others.

This is a trust issue you need to be aware of.
No software in the world can prevent your friends from leaking your confidential and trusted communications.
Only a wise choice of friends.

But it isn't as clear-cut when dealing with GNU Social and other network providers.
If you look at the Contact Edit page for any person, we will tell you whether or not they are members of an insecure network where you should exercise caution.

Once you have created a post, you can not change the permissions assigned.
Within seconds it has been delivered to lots of people - and perhaps everybody it was addressed to.
If you mistakenly created a message and wish to take it back, the best you can do is delete it.
We will send out a delete notification to everybody who received the message - and this should wipe out the message with the same speed as it was initially propagated.
In most cases, it will be completely wiped from the Internet - in under a minute.
Again, this applies to Friendica networks.
Once a message spreads to other networks, it may not be removed quickly, and in some cases, it may not be removed at all.



Profiles, Photos, and Privacy
=============================

The decentralised nature of Friendica (many websites exchanging information rather than one website which controls everything) has some implications with privacy as it relates to people on other sites.
There are things you should be aware of, so you can decide best how to interact privately.

**Photos**

Sharing photos privately is a problem.
We can only share them __privately__ with Friendica members.
In order to share with other people, we need to prove who they are.
We can prove the identity of Friendica members, as we have a mechanism to do so.
Your friends on other networks will be blocked from viewing these private photos because we cannot prove that they should be allowed to see them.

Our developers are working on solutions to allow access to your friends - no matter what network they are on.
However we take privacy seriously and don't behave like some networks that __pretend__ your photos are private, but make them available to others without proof of identity.

**Profiles**

Your profile and "wall" may also be visited by your friends from other networks, and you can block access to these by web visitors that Friendica doesn't know.
Be aware that this could include some of your friends on other networks.

This may produce undesired results when posting a long status message to (for instance) Twitter.
When Friendica sends a post to these networks which exceeds the service length limit, we truncate it and provide a link to the original.
The original is a link back to your Friendica profile.
As Friendica cannot prove who they are, it may not be possible for these people to view your post in full.

For people in this situation we would recommend providing a "Twitter-length" summary, with more detail for friends that can see the post in full.
You can do so by including the BBCode tag *abstract* in your posting.

Blocking your profile or entire Friendica site from unknown web visitors also has serious implications for communicating with GNU Social members.
These networks communicate with others via public protocols that are not authenticated.
In order to view your posts, these networks have to access them as an "unknown web visitor".
If we allowed this, it would mean anybody could in fact see your posts, and you've instructed Friendica not to allow this.
So be aware that the act of blocking your profile to unknown visitors also has the effect of blocking outbound communication with public networks (such as GNU Social) and feed readers such as Google Reader.
