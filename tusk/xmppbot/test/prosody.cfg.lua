admins = { "pub@localhost", "bot@localhost" }
modules_enabled = { "roster"; "saslauth"; "disco"; "ping"; }
allow_registration = true
c2s_require_encryption = false
allow_unencrypted_plain_auth = true
authentication = "internal_plain"
storage = "internal"
log = { { levels = { min = "info" }, to = "console" } }
VirtualHost "localhost"
Component "pubsub.localhost" "pubsub"
  autocreate_on_publish = true
  autocreate_on_subscribe = true
  expose_publisher = true
