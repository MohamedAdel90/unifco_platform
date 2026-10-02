# Production deploy retry

This marker intentionally triggers a fresh production deployment after the previous SSH deploy job stalled while publishing the role-queue tenant-context fix.

Target functional fix already merged on main: `c80b30ab5cf493da3a450e1bee3cb574408b5337`.

No application runtime behavior or production data is changed by this marker.
