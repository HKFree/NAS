import argparse
from http.server import BaseHTTPRequestHandler, HTTPServer
import json
import urllib.parse


class Storage(BaseHTTPRequestHandler):
    folders = {
        name: dict(name=name, space_available=2**40, space_used=2**30, quota=0)
        for name in ('/', '/1234', '/1234/demo')
    }

    def do_GET(self):
        url = urllib.parse.urlsplit(self.path)
        params = urllib.parse.parse_qs(url.query)
        name = params.get('name', [''])[0]
        if url.path == '/list':
            body = list(self.folders.values())
        elif url.path == '/create':
            quota = int(params.get('quota', ['0'])[0])
            self.folders[name] = dict(name=name, space_available=2**30, space_used=0, quota=quota)
            body = {'ok': True}
        elif url.path == '/delete':
            body = {'ok': self.folders.pop(name, None) is not None}
        elif url.path == '/quota':
            quota = params['quota'][0]
            self.folders[name]['quota'] = 0 if quota == 'none' else int(quota)
            body = {'ok': True}
        elif url.path == '/perms':
            body = {'ok': name in self.folders}
        else:
            self.send_error(404)
            return
        self.send_response(200)
        self.send_header('Content-Type', 'application/json')
        self.end_headers()
        self.wfile.write(json.dumps(body).encode())


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--port', type=int, default=18765)
    args = parser.parse_args()
    with HTTPServer(('127.0.0.1', args.port), Storage) as server:
        print(server.server_port, flush=True)
        server.serve_forever()
