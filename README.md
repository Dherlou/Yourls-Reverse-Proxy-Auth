# Reverse Proxy Auth for YOURLS

Authenticate YOURLS users based on a username passed by a reverse proxy in a custom header.

This plugin can be used with any reverse proxy / header, however, it is developed and tested using Traefik with Authentik middleware.

## Features
- Authenticates users via a username header set by your reverse proxy, which in turn can utilize authentication middleware (e.g., Authentik, Authelia, etc.).
- Falls back to YOURLS internal authentication if the header is not present.
- Single Logout (SLO) redirection.

## Requirements
- YOURLS (tested with YOURLS 1.9+)
- a reverse proxy that secures yourls and injects a username header (default: `X-authentik-username`).

## Security Note
- Do not expose YOURLS directly to the internet without the reverse proxy in place.
- Set up your reverse proxy to only let authenticated users access the `/admin` area of Yourls.<br/>Exclude everything outside the admin area from authentication enforcement to allow unauthenticated access to your short URLs.
- Ensure your reverse proxy always sets/overwrites the authentication header.

## Installation
1. Download or clone this repository into your YOURLS `user/plugins` directory:
   ```sh
   git clone https://github.com/Dherlou/Yourls-Reverse-Proxy-Auth.git reverse-proxy-auth
   ```
2. Activate the plugin from the YOURLS admin interface.

## Configuration

### Yourls plugin
The plugin can be configured using environment variables:

| Variable                         | Description                                                      | Default                    |
|----------------------------------|------------------------------------------------------------------|----------------------------|
| `YOURLS_AUTH_USERNAME_HEADER`    | Header variable to read the username from.                       | `HTTP_X_AUTHENTIK_USERNAME`|
| `YOURLS_AUTH_SLO_URL`            | URL to redirect to after logout (Single Logout/SLO).  | `/outpost.goauthentik.io/sign_out`                  |
(Tip: If you want a better SLO experience instead of landing at the "empty" Yourls starting page, set the SLO_URL to: `https://<your-authentik-domain>/application/o/<slug>/end-session/` (the last `/` is important!))

### Reverse Proxy (Example: Traefik + Authentik Middleware)

1. Authentik
    1. Set up a `Proxy Provider (Forward Auth)` in Authentik for the domain at which you serve Yourls.
        - `Unauthenticated URLs / Paths`: regex like `^([^a]|a(a|d(a|m(a|ia)))*([^ad]|d([^am]|m([^ai]|i[^an]))))*(a(a|d(a|m(a|ia)))*(d(m?|mi))?)?$`
            - this disables requiring an authenticated session for accessing URLs without "admin" in it
            - regex must be POSIX-compatible (see [StackOverflow](https://stackoverflow.com/questions/1687620/regex-match-everything-but-a-specific-pattern/37988661#37988661), i.e. Authentik's GoLang Regex Interpreter doesn't support negative lookaheads and stuff like that)
            - regex should work as long as neither your domain nor your short-urls contain the "admin" substring<br>**feel free to improve this regex via PR :)**
    2. Create an application, link it to the provider and grant users/groups/policies/entitlements access to it.
    3. Don't forget to add your application to the embedded outpost.
2. Traefik
    1. Create a middleware config for Authentik in Traefik ([docs](https://docs.goauthentik.io/docs/add-secure-apps/providers/proxy/server_traefik)).
    2. Add the middleware to your Yourls route in Traefik.

## License
See [LICENSE](LICENSE) for details.

## Support
For issues or feature requests, please open an issue on the [GitHub repository](https://github.com/Dherlou/Yourls-Reverse-Proxy-Auth).
