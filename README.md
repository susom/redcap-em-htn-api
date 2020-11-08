# HTNapi
This EM is intended to manage the DATA Authorization workflow between the Hypertension Study (HTN) and Omron Wellness, The manufacturer of the Blood Pressure Cuffs
that patients will use at home and sync/upload to the Omron servers.

Providers can instruct their patients to click on a link which will take them to an Omron authorization work flow off site.  Once the patient signs in and grants access. 
Omron will post back to an open endpoint (NOAUTH) provided by this EM (and requires white listing with Omron Dev Portal).

On Postback, this EM will store the patients unique Omron id along with Omron Access Token, Omron Refresh Token and Expire time.
This em via a daily CRON job will monitor and refresh expiring access tokens.

This EM will also provide a webhook, which OMRON will ping everytime a patient has synced new data.  Once pinged, the EM will make a call on the Patients behalf and pull their newly uploaded data via the Omron API and store it to their record in REDCap.

