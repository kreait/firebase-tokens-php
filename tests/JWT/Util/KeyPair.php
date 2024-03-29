<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Util;

/**
 * @internal
 */
final class KeyPair
{
    /**
     * @return non-empty-string
     */
    public static function privateKey(): string
    {
        return <<<'PRIVATE_KEY'
-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQC73IqjjFLQVBQr
U68OFus62MkhQo4rPs1/8IYtr+vv7v4iPsvHhg9j493wp5Pw/wU5PlmMC+7zTt9O
IFbf/r/PI/GqKgb2iH3p+eSY+Tc3dO6e0Hs4MuoS4cfi+rBcSlZMh8OB3jEDz07q
Y3VbQhxzlcEF3Qo5mUjgCqQayzoJ4FxNb60po6OeoqoOYWEhR2OxWhDlsrxo71yk
8uDe5bGYRQICIsUaVt86Wnezeoa1y+mphMNx11JNE0ngvNr1hq8hYDyssqIaS2ex
HwJXhLHD9UwRECn3yDeZnSkJFGQmpx7vZP+RMrhQuQQYqujCcqRI2gZnHDaOV1lm
f5dOwWB1AgMBAAECggEAUsu8fqBVz2N/ECltubP4MJNi8bm3lu+y+nQzbudeAP1A
HC+4+FLpbYj8RBhXZ5u93aDRLpwD0FAusuwl3csVFmItHGYxc25ssDZmvdT4tQRg
NraD2Bz4dSH1SuBZ4hMRPeGIFTCsQZWYnkz/aB0XFQonbEIjQ4d/St7lvLlc9wSd
2KG4uNxQCqk+1SJxTzq9LhlqsNKQHlMDhjjiiF8b82xCynufOSHoRtjMXsYhZ7+B
jtr4IqgYgaBOABMJUjKYV7kCV41oq4oDyG8nY+L/MtJUVBK7TrpqU1unWZzHw5GQ
bdTfyPtIKqIV8Y2nGV34mWldh6NbYQTxrjVfyJ1Y8QKBgQDdkin5i89hI3+g72zt
/Uw9Kz+t/p2B9wyYI2HFLkq/SuDUMmrJsN5tDcUAzHfhxikkUOzLhjpigNZ0moOW
WB+4JaNIhDG9M4tgvHwHp1ZBI2CqaEyWN7JCgd10ntoykP1FB3ARQ5t2KIb5EeeE
NGEKIEdbiHgCxrPwz+l8Ln24RwKBgQDZDXRGFPmDDGiLR7arou9OSzGr4PlaxPU4
xzoH7o+iZ/qDDKzMresoG6DQ61A2RfQoTru2kVB5TSuAFFVVcZWGwbn1HEZTQWm1
Chg4mJ65m20uRtFoss+3o3K/4+XgUz/0pHG+WvbvATaupCyk+u7OYo4jk0w3/syL
aOWbhB97YwKBgAiY9lX/jdF4HiixgamObZnmBreKrLPxUSTKIq4TCMV5c1Xoiuo+
mbLjmORaCsDQ/qGxHi8bi0JtO2UU5cw8qSZtF3Pl5UQxLtRXG/z0Ck3GwKZ8G5Ss
npckEOLIkzDpHVrDWh7hX7PrCKm7fx9LJQTOkdZEalu5OBw9BRNTfn9bAoGBALXD
oJV3xyNJZtsMaRr+zWxBaA1Jz0eGHN05aY1u5/XXIWBRYvvcwUrLKDcMeBWbK0X9
+RCATGXoi/8sB/IPtmotHW74CKR76Ovk0jfDB1jjoeDZCVCmPXDJfbTYQo9C6BIV
C/Oe9Z9c4tAJSCG4yfcnbWS5W2ChDeXJKE69rCeFAoGBAKROH4cnFV8F64PFuuAg
QMijlMePdwfiEmWGezFRfe9uUbeRhm2ZBlaPwpFHNAxQRRe8qHYZ/U0tTBuxmYaK
wIxTM/dHkl+Ucl83XROKA8h7UWJEjo8m/LOHxt3xUnyz/gC5bM52zDx6jIWtzlRX
MjWLxp+hp7CV4l+OrM66UL5v
-----END PRIVATE KEY-----
PRIVATE_KEY;
    }

    /**
     * @return non-empty-string
     */
    public static function publicKey(): string
    {
        return <<<'PUBLIC_KEY'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAu9yKo4xS0FQUK1OvDhbr
OtjJIUKOKz7Nf/CGLa/r7+7+Ij7Lx4YPY+Pd8KeT8P8FOT5ZjAvu807fTiBW3/6/
zyPxqioG9oh96fnkmPk3N3TuntB7ODLqEuHH4vqwXEpWTIfDgd4xA89O6mN1W0Ic
c5XBBd0KOZlI4AqkGss6CeBcTW+tKaOjnqKqDmFhIUdjsVoQ5bK8aO9cpPLg3uWx
mEUCAiLFGlbfOlp3s3qGtcvpqYTDcddSTRNJ4Lza9YavIWA8rLKiGktnsR8CV4Sx
w/VMERAp98g3mZ0pCRRkJqce72T/kTK4ULkEGKrownKkSNoGZxw2jldZZn+XTsFg
dQIDAQAB
-----END PUBLIC KEY-----
PUBLIC_KEY;
    }
}
