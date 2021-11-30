# Google Sync

Wordpress plugin for synchronizing wordpress users with Google Workspace.

## Setup

1. Go to https://console.cloud.google.com/apis/credentials and create a new service account
2. On the serive account, add a new JSON key and save it somewhere on the server. Note the Unique ID for the service account
3. Also from https://console.cloud.google.com/apis/credentials create a OAuth Client ID with the Web Application type. Make sure `site_url( '?ftek_gsync_openid' )` is added as a Authorized redirect URI. Save the OAuth Client data in JSON format somewhere on the server.
4. Go to https://admin.google.com/ac/owl/domainwidedelegation, add a new API clinet with the Unique ID from before. The neccessary scopes are
   * https://www.googleapis.com/auth/admin.directory.user.readonly
   * https://www.googleapis.com/auth/admin.directory.orgunit.readonly
5. Enable the Admin API from https://console.developers.google.com/apis/api/admin.googleapis.com/overview
6. Your organizations Customer ID can be found at https://admin.google.com/ac/accountsettings
