catlight-github-actions
=======================

Show results of [GitHub Actions](https://github.com/features/actions)
in [Catlight](https://catlight.io/).

![](https://github.com/dracos/catlight-github-actions/blob/main/images/catlight.jpeg?raw=true)

## Installation

1. Clone this repo on a server somewhere.

2. Copy config.json-example to config.json.

3. Edit it to include one or more github accounts in `orgs`, each
   of which can have one or more repositories you wish to monitor
   in `buildDefinitions`. For example, I could have `mysociety`
   as an org ID, with `fixmystreet` as a buildDefinition.

   Also include your GitHub username and a personal access token with
   `public_repo` or `repo` access (otherwise you’ll hit the per-hour limit).

4. In Catlight, go to Edit, Add new connection

   ![](https://github.com/dracos/catlight-github-actions/blob/main/images/edit.jpeg?raw=true)

5. Pick Catlight-compatible

   ![](https://github.com/dracos/catlight-github-actions/blob/main/images/type.jpeg?raw=true)

6. Enter the URL to the location of the catlight.php script, click Connect.

## How it works

It uses the GitHub API to fetch the workflow run information, and then translates the
data into the [Catlight Protocol format](https://github.com/catlightio/catlight-protocol).
