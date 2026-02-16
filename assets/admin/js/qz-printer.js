/* =====================================================
   CERTIFICATE
===================================================== */

qz.security.setCertificatePromise(function (resolve, reject) {

    resolve(`-----BEGIN CERTIFICATE-----
MIIDxzCCAq+gAwIBAgIUL+1ANo6tvEBOu/vVHSeJaCPfUL4wDQYJKoZIhvcNAQEL
BQAwgYsxCzAJBgNVBAYTAlBIMREwDwYDVQQIDAhCYXRhbmdhczEQMA4GA1UEBwwH
TmFzdWdidTESMBAGA1UECgwJR1ZGTE9SSURBMQswCQYDVQQLDAJHVjELMAkGA1UE
AwwCR1YxKTAnBgkqhkiG9w0BCQEWGmpvc2h1YS5maXJtZUBtYWtvcGEub25saW5l
MB4XDTI2MDIxNjAyNTY1OFoXDTM2MDIxNDAyNTY1OFowgYsxCzAJBgNVBAYTAlBI
MREwDwYDVQQIDAhCYXRhbmdhczEQMA4GA1UEBwwHTmFzdWdidTESMBAGA1UECgwJ
R1ZGTE9SSURBMQswCQYDVQQLDAJHVjELMAkGA1UEAwwCR1YxKTAnBgkqhkiG9w0B
CQEWGmpvc2h1YS5maXJtZUBtYWtvcGEub25saW5lMIIBIjANBgkqhkiG9w0BAQEF
AAOCAQ8AMIIBCgKCAQEA6uUKna8o9SZ9mi17VZctefCsnxDJTH6RvFgc8Gi5o/XY
ASMSmLcUCwuY+GSUmZrOL9Re9dpHeXQPTHccgD7jfQje+zEMaHrHJCLS4J9YHlOj
HUA69v5Dciv/kyCUOBuzGpb4Cn1A1iIqMoihUP0IqqmvJgPwuIiAiPf9C3nw6s/t
u1ClgYEyfcBnvKuaQMAnz646VJSC/DT06uWb2C7+nZSZiTNjklXer6l5lRUuitXI
36isGwseXMSkyl8K4JOMcq06yNW5t2lVEnD0EJyT1IThMZhi9z4GpYuNPCkTxS8u
ecp4LU/wp3GzqXLJJYuzf70WkjiJKgqPAgjcuyzJWwIDAQABoyEwHzAdBgNVHQ4E
FgQUalAwII6KlkQBpnmQiddHA50y4MEwDQYJKoZIhvcNAQELBQADggEBAItS4N0q
drNrRSZ7sNns46DIpmcEx5/mWdu/UW+pXF2/SGH6vz3gPjVGRRsTAElLBIDjp8S0
x3rZH+WxiSKntWcT/DQAzyhZK/CfDQHYBzZsQHhJ/phAsM5/DKinIByfh1t+X4uK
yZWV7TXpH0Lnis4sgT0vFhz4Z6mOm+O9zo8hjXZg0twOCy3zuHjApzIcY6Gj6aRB
5AbQrHxt0GGHmbVYdcyxndJ1x/luid7J52/2QI6nze5r88rLxrXlLz80RHl0MqPO
KlLgxzWgQVeKww41g0gmwZZqydKDTayoHiml0V5/NNYio3rMilioDQCDc7t/e5KG
CIi4il8SxWsN43U=
-----END CERTIFICATE-----`);

});


/* =====================================================
   SIGNATURE
===================================================== */

const currentUrl = new URL(window.location.href);
var base = '/';

if (window.location.protocol === "http:") {
    base = "http://localhost/gv-florida/"
}

qz.security.setSignatureAlgorithm("SHA512"); // Since 2.1
qz.security.setSignaturePromise(function (toSign) {
    return function (resolve, reject) {
        fetch(base + "api/qz/sign?data=" + toSign, {
                cache: 'no-store',
                headers: {
                    'Content-Type': 'text/plain'
                },
            })
            .then(function (data) {
                data.ok ? resolve(data.text()) : reject(data.text());
            });
    };
});


/* =====================================================
   CONNECTION
===================================================== */

function connectQZ() {

    if (!qz.websocket.isActive()) {

        return qz.websocket.connect();

    }

    return Promise.resolve();
}


/* =====================================================
   GET PRINTER
===================================================== */

function getPrinter() {

    return qz.printers.getDefault();
}