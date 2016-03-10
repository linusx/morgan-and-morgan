# Morgan and Morgan Plugin

Write a WordPress plugin that ingests submissions to self.wordpress on the WordPress subreddit https://www.reddit.com/r/wordpress into WordPress as post type 'post.' 

Some notes:

- The title of the post should be the object's `title` value
- The body of the post should be the object's `selftext`
- Store whatever relevant information from the objects on the post you feel is necessary.
- Be sure to use post meta for any field (the field itself does not matter, just store some post meta). Feel free to use Advanced Custom Fields for this piece.

---

For the source data: 
- Use the JSON API https://www.reddit.com/r/wordpress.json for the data.
- Hint: only use objects that have the value 'self.wordpress' for domain


---

* Use a WP_Cron for ongoing ingestion.
* This should not create duplicate posts.
* The deliverable should be a standalone plugin that could run on any WordPress installation (optionally with the ACF plugin for storing meta). It can be delivered as a zip. 
* If there are any exports of ACF Field Definitions that can be submitted as well.

---


This will be tested on a bare install of WordPress using the underscores theme. Frontend output is not necessary.

Let us know how long it took and if you have any questions.
