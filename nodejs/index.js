/**
* Module dependencies.
*/

var express = require('express');

// pass the express to the connect memcached module
// allowing it to inherit from express.session.Store
var MemcachedStore = require('connect-memcached')(express);

var app = express();

app.use(express.favicon());

// request logging
app.use(express.logger());

// required to parse the session cookie
app.use(express.cookieParser());

// Populates:
// - req.session
// - req.sessionStore
// - req.sessionID (or req.session.id)

app.use(express.session({
  secret: false,
  store: new MemcachedStore({
    hosts: [ '127.0.0.1:11211' ] // Change this to your memcache server(s). See Options for additional info.
  })
}));

app.get('/', function(req, res){
  if (req.session.views) {
    ++req.session.views;
  } else {
    req.session.views = 1;
  }
  res.send('Viewed <strong>' + req.session.views + '</strong> times.');
});

app.listen(3000);
console.log('Express app started on port 3000');