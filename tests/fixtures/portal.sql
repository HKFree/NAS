CREATE TABLE folder (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    user_id INTEGER NOT NULL,
    dateCreated INTEGER NOT NULL,
    comment TEXT NOT NULL,
    dedicatedShare INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE shareType (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    humanName TEXT NOT NULL,
    enabled INTEGER NOT NULL,
    dedicated INTEGER NOT NULL
);
CREATE TABLE share (
    id INTEGER PRIMARY KEY,
    folder_id INTEGER NOT NULL REFERENCES folder(id),
    shareType_id INTEGER NOT NULL REFERENCES shareType(id),
    var TEXT,
    var2 TEXT
);

INSERT INTO folder VALUES (1, '/1234', 1234, 0, 'Home', 0), (2, '/1234/demo', 1234, 0, 'Demo', 0);
INSERT INTO shareType VALUES
    (1, 'ftp', 'FTP', 1, 0),
    (2, 'webdav', 'WebDAV', 1, 0),
    (3, 'nfs', 'NFS', 1, 0),
    (5, 'rsync', 'Rsync', 1, 0),
    (6, 'nextcloud', 'Nextcloud', 1, 1);
INSERT INTO share VALUES
    (1, 2, 1, 'fixture-user', 'fixture-share-password'),
    (2, 2, 2, 'fixture-webdav', 'fixture-dav-password');
