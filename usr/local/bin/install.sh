#!/bin/bash

echo "Appserver Kurulumu Başlıyor..."

if [ "$EUID" -ne 0 ]; then
    echo "Bu betiği root olarak çalıştırmanız gerekiyor."
    exit 1
fi

echo "Sistem güncelleniyor..."
yum update -y || apt update -y && apt upgrade -y

echo "Bağımlılıklar yükleniyor..."
yum install -y httpd php php-mysql php-gd php-zip php-mbstring php-json php-imap mariadb-server postfix dovecot bind bind-utils proftpd unzip perl curl wget git openssh-server cpan mod_security2 mailman quota openssh-clients ruby python3 python3-pip screen htop nmap || \
apt install -y apache2 php libapache2-mod-php php-mysql php-gd php-zip php-mbstring php-json php-imap mariadb-server postfix dovecot-core dovecot-imapd bind9 proftpd unzip perl curl wget git openssh-server libapache2-mod-security2 mailman quota openssh-client ruby python3 python3-pip screen htop nmap

mkdir -p /usr/local/appserver/{whm,cpanel,webmail,ssl,logs,backups,bin,3rdparty/roundcube/config,3rdparty/roundcube/SQL,lib,scripts,skeleton,data,modsec_rules}
cd /usr/local/appserver

cat <<EOL > /etc/httpd/conf.d/appserver.conf || cat <<EOL > /etc/apache2/sites-available/appserver.conf
Listen 2082
<VirtualHost *:2082>
    DocumentRoot /usr/local/appserver/cpanel
    ServerName localhost
</VirtualHost>

Listen 2083
<VirtualHost *:2083>
    DocumentRoot /usr/local/appserver/cpanel
    ServerName localhost
    SSLEngine on
    SSLCertificateFile /usr/local/appserver/ssl/server.crt
    SSLCertificateKeyFile /usr/local/appserver/ssl/server.key
</VirtualHost>

Listen 2086
<VirtualHost *:2086>
    DocumentRoot /usr/local/appserver/whm
    ServerName localhost
</VirtualHost>

Listen 2087
<VirtualHost *:2087>
    DocumentRoot /usr/local/appserver/whm
    ServerName localhost
    SSLEngine on
    SSLCertificateFile /usr/local/appserver/ssl/server.crt
    SSLCertificateKeyFile /usr/local/appserver/ssl/server.key
</VirtualHost>

Listen 2095
<VirtualHost *:2095>
    DocumentRoot /usr/local/appserver/webmail
    ServerName localhost
</VirtualHost>

Listen 2096
<VirtualHost *:2096>
    DocumentRoot /usr/local/appserver/3rdparty/roundcube
    ServerName localhost
    SSLEngine on
    SSLCertificateFile /usr/local/appserver/ssl/server.crt
    SSLCertificateKeyFile /usr/local/appserver/ssl/server.key
</VirtualHost>
EOL

[ -f /etc/apache2/sites-available/appserver.conf ] && ln -s /etc/apache2/sites-available/appserver.conf /etc/apache2/sites-enabled/

systemctl enable mariadb && systemctl start mariadb
mysql_secure_installation <<EOF

y
rootpassword
rootpassword
y
y
y
y
EOF

echo "inet_interfaces = all" >> /etc/postfix/main.cf
echo "mydestination = \$myhostname, localhost.\$mydomain, localhost" >> /etc/postfix/main.cf
sed -i 's/#protocols = imap pop3/protocols = imap pop3/' /etc/dovecot/dovecot.conf
echo "mail_location = maildir:/home/%u/Maildir" >> /etc/dovecot/conf.d/10-mail.conf
systemctl enable postfix dovecot mailman && systemctl start postfix dovecot mailman

cat <<EOL >> /etc/named.conf || cat <<EOL > /etc/bind/named.conf.local
zone "$(hostname)" {
    type master;
    file "/var/named/$(hostname).db";
};
EOL
cat <<EOL > /var/named/$(hostname).db || cat <<EOL > /var/cache/bind/$(hostname).db
\$TTL 86400
@   IN  SOA ns1.$(hostname). admin.$(hostname). (
        2025022401  ; Serial
        3600        ; Refresh
        1800        ; Retry
        604800      ; Expire
        86400       ; Minimum TTL
)
@   IN  NS  ns1.$(hostname).
@   IN  A   $(hostname -I | awk '{print $1}')
ns1 IN  A   $(hostname -I | awk '{print $1}')
EOL
systemctl enable named || systemctl enable bind9
systemctl start named || systemctl start bind9

systemctl enable proftpd sshd && systemctl start proftpd sshd

echo "SecRuleEngine On" >> /etc/httpd/conf.d/mod_security.conf || echo "SecRuleEngine On" >> /etc/apache2/mods-enabled/security2.conf
echo "Include /usr/local/appserver/modsec_rules/*.conf" >> /etc/httpd/conf.d/mod_security.conf || echo "Include /usr/local/appserver/modsec_rules/*.conf" >> /etc/apache2/mods-enabled/security2.conf

quotacheck -cum / && quotaon /

echo "Roundcube indiriliyor ve kuruluyor..."
wget -O roundcube.tar.gz https://github.com/roundcube/roundcubemail/releases/download/1.6.9/roundcubemail-1.6.9-complete.tar.gz
tar -xzf roundcube.tar.gz -C /usr/local/appserver/3rdparty/roundcube --strip-components=1
rm roundcube.tar.gz
chown -R apache:apache /usr/local/appserver/3rdparty/roundcube || chown -R www-data:www-data /usr/local/appserver/3rdparty/roundcube

mysql -u root -prootpassword <<EOF
CREATE DATABASE roundcube;
GRANT ALL PRIVILEGES ON roundcube.* TO 'roundcube'@'localhost' IDENTIFIED BY 'roundcube_pass';
FLUSH PRIVILEGES;
EOF
mysql -u root -prootpassword roundcube < /usr/local/appserver/3rdparty/roundcube/SQL/mysql.initial.sql

systemctl enable httpd || systemctl enable apache2
systemctl restart httpd || systemctl restart apache2

echo "Appserver başarıyla kuruldu!"
echo "WHM: https://$(hostname -I | awk '{print $1}'):2087"
echo "cPanel: https://$(hostname -I | awk '{print $1}'):2083"
echo "Webmail: https://$(hostname -I | awk '{print $1}'):2096"