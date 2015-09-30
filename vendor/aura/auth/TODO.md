# TODO

## Remember Me

Add "remember me" functionality.

On "remember me" during login, store the a cryptographically secure token as a cookie. (Store username too?) Also keep in database.

On resume, if resume session fails, look for that cookie.

    If no cookie, no remember.

    If cookie, check against database.

        If cookie matches, remember that user into the session (Status::REMEMBERED). Update the token and cookie.

        If cookie no match, treat user as anon.

Also on resume, we may wish to add a DB check to reload session details; this is in case there have been admin changes to the user.

Cf. <https://github.com/craigrodway/LoginPersist/blob/master/LoginPersist.module> and perhaps other implementations for ideas and insight.

## Verifiers

Build an HttpDigestVerifier based on <http://php.net/manual/en/features.http-auth.php> and/or <http://evertpot.com/223/>.

## Security

Track IP numbers through _ResumeService_? This may break with proxies.

## Throttling

Track activity/page loads?  I.e., number of times we had to "resume" the session. This would be for throttling the page loads.

Track number of login attempts? This would be for throttling brute-force of logins.

