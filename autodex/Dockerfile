FROM node:14.17.4 AS base

WORKDIR /app

COPY package.json package-lock.json ./

RUN npm install --unsafe-perms

FROM base AS server

COPY server ./server
COPY lib ./lib

COPY tsconfig.base.json ./

RUN npm run build:server

CMD [ "npm", "run", "serve:server" ]

FROM base AS client

COPY client ./client
COPY lib ./lib
COPY tsconfig.base.json ./

RUN npm run build:client

CMD [ "npm", "run", "serve:client" ]
