=== Ninja Job Board ===
Contributors: techjewel, wpmanageninja
Tags: job board, career, job listing, job manager, recruitment
Requires at least: 6.0
Tested up to: 7.1
Stable tag: 2.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Ninja Job Board is a light-weight plugin that adds a job board along with application form to your WordPress website.

== Description ==

Ninja Job Board is made for small and medium organizations to reduce their pain on hiring new people. The ultimate goal of this job board plugin is to make the recruitment process as easy as possible with a little time invested.

Ninja Job Board is a self-hosted WordPress Plugin where you can build a questionnaire form by drag and drop form builder.

<b>The Following Form fields are available:</b>

- Applicant Name
- Applicant Email
- CV Upload Field
- File Upload Field
- Single Line Text
- Texarea Field
- Number Field
- Dropdown Select Field
- Radio Choice Field
- CheckBoxes
- Date Field
- Hidden Input Field
- Markup HTML
- Form Steps to breakdown your form to several pages.
- More is coming soon.


#### Why another WordPress job board plugin?
The developers of <a href="https://wordpress.org/plugins/ninja-tables/">Ninja Tables</a> went through the stress, and they wanted one excellent solution for that. As a result, this plugin sees the light and is presented to the world for making others beneficial, too.

#### Application processing via emails sucks
It's a real challenge for startups and small enterprises to recruit talents painlessly. Using a job portal is costly, and accepting applications via emails is distressing and messy because it's hard to keep track of all the emails and sort and manage applicants into categories.

#### The easiest way to create listings
Ninja Job Board makes it plain and simple. Every time you want to hire for your company, create a job listing on your website. The listings are effective, well-organized, and above all, easy to manage.

#### No need to train your HR Manager
As the tool implies with simplicity, you don't need to train your HR manager to know how to run it. The tool itself is self-explanatory means anyone can understand what's going on right after entering it.

#### Application filtering and search
From creating a new application real fast to keeping track of every single application, Ninja Job Board is meant for success. Besides sorting the data based on categories, you can apply search and filter on the table.

#### A clean and eye-pleasing interface
The UI is pretty simple that encourages users to interact more without any fear of making things complicated. Additionally, the admin panel is created carefully to ensure better user experience. This is the perfect job listing plugin for smart recruiters.

#### Application Management
With built-in application management system, you can see the total applications of your job post. You can also set application status as well as internal status.

In individual application view page, you can see the complete submitted data, uploaded applicant cv. You can also change the application status and internal status easily. One of the important feature is, you can add notes to each application.

#### Email Broadcast to Applicants
Send email to Applicants as bulk and you can select filter by application status and internal status before send.


#### Super fast and Light Weight Form
This is a super fast form builder and it's only loads css and js if you add the form in a page. Every line of CSS and JS is useful and there has no extra css and js. For a normal form, It's loads less than 30KB css and js combined. This form builder plugin will not slow down your site. it's the fastest form builder plugin in WordPress. It has also a nice interface to give detailed information of your job post.

### KEY FEATURES
- Add new job posting on your WordPress site, whenever you want
- The UI is clean and simple
- Searching and filtering options available for job listings
- Make 'Shortlists' easily based on your criteria
- Print the application data if it's necessary
- Intuitive form creation via readymade input fields
- Attach file option to include a CV
- Create multiple job listings at a time and check on them separately
- It's easy to operate by anyone
- All submitted data are easily searchable and filterable
- Add a note against an application to mark it for reviewing in future
- View the uploaded CV as a PDF
- Notify the applicants and HR manager simultaneously after an application is submitted
- Schedule the application to open at a specific time
- Categorize the application based on its status


#### Features Tracker
- Job Posting with information *(DONE)*
- Application Form Builder *(DONE)*
- CV Management System *(Done)*
- Application status management *(Done)*
- Filter Applications based on status *(Done)*
- Search Applications with keywords/full text search *(Done)*
- Email Notification System management *(Done)*
- Send bulk emails to the applicants and filter by application status *(Done)*
- Export Data as CSV/Excel/JSON *(On development)
- Application Assesment for HR Department (On development)

This plugin is a very planned project, and we have a long term development plan for this. We already invested around 200 human working hours (+coffees) on it, and we have the intention to spend much more on it. Please let us know what features you want, and we are happy to add that in our development sprint.

The full source code is available on <a href="https://github.com/WPManageNinja/wp-job-board">Github</a>.


== Other Plugins by WPManageNinja ==
<ul>
<li><a href="https://wordpress.org/plugins/ninja-tables/" target="_blank">Ninja Tables – WP Data Table Plugin for WordPress</a></li>
<li><a href="https://wordpress.org/plugins/fluentform/" target="_blank">WP Fluent Form – Contact Form Plugin for WordPress with Advanced Form Builder Features</a></li>
<li><a href="https://wordpress.org/plugins/wp-payment-form/" target="_blank">WPPayForm for Stripe WordPress Payments Plugin</a></li>
</ul>


== Installation ==

This section describes how to install the plugin and get it working.
e.g.

1. Upload the plugin files to the /wp-content/plugins/ directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the ‘Plugins’ screen in WordPress
1. Use the WPPay Forms -> All Forms -> Add a Job Posting and get started.


== Frequently Asked Questions ==

= How to create a job listing? =

Go to Ninja Job Board from WordPress admin panel. Create 'Add new job' and give it a 'Title'. Describe the job and add other information: no. of vacancies, years of experience, etc.

= How to display the job posts on the website? =
Each Job post will have it's own permalink to show.

=  How to customize job applications? =
You can easily customize the application process from the dashboard.
Check this doc to know the application customization

= Can I change an application form after it's published? =
Yes, you can change it from the backend and modify with your latest information.

= What will the users see after submitting an application? =
You can set a thank you page to redirect users there or use a custom URL right from the setting.

= How can I show all the current open job postings? =
Use the shortcode <b>[wp_job_list]</b> to show the open job listings.

= How to notify the applicants that their application is submitted successfully? =
From email setting, you can set it up who will get notified about the submissions. You can include the HR manager and other persons as well.

= Who can open uploaded CVs and other application files? =

Only logged-in users who are allowed to view the application. Every file link points to `wp-admin/admin-post.php?action=wpjb_download_file`, which checks the permission before it sends the file. On Apache, the upload folder `wp-content/uploads/wpjobboard/` also blocks direct access with an `.htaccess` file.

On Nginx, `.htaccess` files are ignored. Add this rule to your server block so nobody can open the folder directly:

`location ^~ /wp-content/uploads/wpjobboard/ { deny all; return 403; }`

= How do I stop spam applications? =

A hidden honeypot field is always on. For stronger protection, turn on Cloudflare Turnstile in Settings > General and add your Turnstile site key and secret key.

= Can applicants ask for their data to be exported or erased? =

Yes. Ninja Job Board adds job applications to the WordPress privacy tools (Tools > Export Personal Data and Tools > Erase Personal Data). Erasing deletes the applications of that email address, their activity log and their uploaded files.

= What happens to my data when I delete the plugin? =

Nothing, by default. To remove all jobs, applications, uploaded files and settings when you delete the plugin, turn on "Delete all data when the plugin is deleted" in Settings > General first.

== External services ==

This plugin can connect to Cloudflare Turnstile, a security check that blocks spam on the public application form. It is off by default and only works after you turn it on and add your keys in Settings > General.

When it is on, the application form loads the Turnstile script from `challenges.cloudflare.com`, and the visitor's browser sends Cloudflare the data it needs to tell people from bots. When an application is submitted, your site sends the Turnstile token, your secret key and the visitor's IP address to `https://challenges.cloudflare.com/turnstile/v0/siteverify` to check the token.

Cloudflare Turnstile: [Terms of Use](https://www.cloudflare.com/website-terms/), [Privacy Policy](https://www.cloudflare.com/privacypolicy/).

== Screenshots ==
1. All Job Posts
2. Form Builder for your job post
3. Email Notification Settings
4. Frontend Job posting details
5. Submitted Job Applications Table
6. Single Job Application Data Details
7. Job Details Edit Screen
8. Current Active Jobs Shortcode [wp_job_list]

== Changelog ==

= 2.0.0 - October 30, 2026 =

Security
- Fixed stored XSS through file upload values in the application view
- All submitted field values are sanitized on save and escaped on display
- File uploads are checked against a server-side record of uploaded files
- Uploaded files (CVs) are served only to users who can view the application; direct access to the upload folder is blocked on Apache (see the FAQ for Nginx)
- The admin uses the WordPress REST API; every route checks the user's capability and, for "own forms" permissions, the form owner
- Nonce checks on the public form and file upload
- Users with only the "add form" permission can no longer edit forms of other users
- Email notification placeholders are escaped and email headers are sanitized
- Confirmation page links use an unguessable hash
- The job meta box saves only the known job fields, with nonce and capability checks
- Form import accepts only known form settings
- CSV export protects against spreadsheet formula injection

New
- Redesigned admin that matches the Fluent plugins (FluentCRM, Fluent Support): one app header, job tabs, settings sidebar, clean tables and cards
- Applications filter by status with tabs that show the count for each status
- Color-coded status badges for job posts, application status and internal status
- Application view with an activity timeline, a status panel and compact file attachments
- A job opens on its applications
- Personal data export and erase for applicants (WordPress privacy tools) and suggested privacy policy text
- Spam protection: a honeypot field on every form and optional Cloudflare Turnstile
- Option to delete all plugin data when the plugin is deleted

Improvements
- Admin app rebuilt with Vue 3, Element Plus and Vite
- Smaller admin bundle; removed unused libraries (Spout, Ace editor, vue-html-to-paper, lodash)
- Campaign emails are claimed before sending, so an email is never sent twice

Fixes
- Turning off IP address logging now really stops storing the IP address
- Deleting an application or a job also deletes its uploaded files
- Form scheduling "off" clears the start and end timestamps
- Fatal errors in multi-step forms and the `{other.user_ip}` default value on PHP 8
- Email notification From and Reply-To headers used the unparsed template values
- Status labels "Initial rejected" and "Shortlisted"
- PHP 8.2+ deprecation notices

Removed
- The "Recommended Plugins" box on the application screen

Requirements
- PHP 7.4 or newer; tested with WordPress 7.1

Developers
- `window.WPJobBoard` keeps the hooks API (applyFilters, addFilter, addAction, doAction) and now also exposes `app` and `router`
- New filters: `wpjobboard/spam_check`, `wpjobboard/verify_public_nonce`, `wpjobboard/client_ip`

= 1.4.0 - December 12, 2023 =
- Database Query Issue Fixed

= 1.3.3 - July 20, 2022 =
- Security Fix for File Index for NGINX indexed enabled servers
- Fix issues for esc_* functions

= 1.3.1 - January 21, 2022 =
- Added feature to show other job applications by same applicant
- Added a new feature to send bulk emails to the applicants.
- Fixed text-domain issues
- Fixed email sending issues
- Few other performance improvements

= 1.2.2 - July 23, 2019 =
- Added Bulk email feature where you can send emails to applicants

= 1.0.0 - July 22, 2019 =
- Initial Release

== Upgrade Notice ==

= 2.0.0 =
Security release with a redesigned admin. Fixes a stored XSS issue and protects uploaded CVs. Requires PHP 7.4. On Nginx, add the rule from the FAQ to block direct access to uploaded files.
