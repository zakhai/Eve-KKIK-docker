FROM alpine:edge
RUN apk update && apk add openvswitch=2.9.2-r0 && rm -rf /var/cache/apk/*
VOLUME /etc/openvswitch/
ADD boot.sh /bin/boot.sh
CMD /bin/sh /bin/boot.sh
