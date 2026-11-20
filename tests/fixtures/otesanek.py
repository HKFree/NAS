from http.server import BaseHTTPRequestHandler, HTTPServer
import json
import urllib.parse

folders = {
    name: dict(name=name, space_available=2**40, space_used=2**30)
    for name in ('/', '/1234', '/1234/demo')
}

class Storage(BaseHTTPRequestHandler):
    def do_GET(self):
        url = urllib.parse.urlsplit(self.path)
        name = urllib.parse.parse_qs(url.query).get('name', [''])[0]
        if url.path == '/list':
            body = list(folders.values())
        elif url.path == '/create':
            folders[name] = dict(name=name, space_available=2**30, space_used=0)
            body = {'ok': True}
        elif url.path == '/delete':
            folders.pop(name, None)
            body = {'ok': True}
        elif url.path in ('/quota', '/perms'):
            body = {'ok': True}
        else:
            self.send_error(404)
            return
        self.send_response(200)
        self.send_header('Content-Type', 'application/json')
        self.end_headers()
        self.wfile.write(json.dumps(body).encode())

HTTPServer(('127.0.0.1', 18765), Storage).serve_forever()
