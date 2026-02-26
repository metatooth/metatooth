FROM node:14.17.4 as base

WORKDIR /app

COPY package.json package-lock.json ./

RUN npm install --unsafe-perms

FROM base as server

COPY server ./server
COPY lib ./lib

COPY tsconfig.base.json ./

RUN npm run build:server

CMD [ "npm", "run", "serve:server" ]

FROM base as client

COPY client ./client
COPY lib ./lib
COPY tsconfig.base.json ./

RUN npm run build:client

CMD [ "npm", "run", "serve:client" ]
