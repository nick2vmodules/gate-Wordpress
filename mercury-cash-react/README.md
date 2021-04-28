# Getting Started with Create React App

This project was bootstrapped with [Create React App](https://github.com/facebook/create-react-app).

## Available Scripts

In the project directory, you can run:

### `yarn start`

Runs the app in the development mode.\
Open [http://localhost:3000](http://localhost:3000) to view it in the browser.

The page will reload if you make edits.\
You will also see any lint errors in the console.

### `yarn test`

Launches the test runner in the interactive watch mode.\
See the section about [running tests](https://facebook.github.io/create-react-app/docs/running-tests) for more
information.

### `yarn build`

Builds the app for production to the `build` folder.\
It correctly bundles React in production mode and optimizes the build for the best performance.

The build is minified and the filenames include the hashes.\
Your app is ready to be deployed!

See the section about [deployment](https://facebook.github.io/create-react-app/docs/deployment) for more information.

### `yarn eject`

**Note: this is a one-way operation. Once you `eject`, you can’t go back!**

If you aren’t satisfied with the build tool and configuration choices, you can `eject` at any time. This command will
remove the single build dependency from your project.

Instead, it will copy all the configuration files and the transitive dependencies (webpack, Babel, ESLint, etc) right
into your project so you have full control over them. All of the commands except `eject` will still work, but they will
point to the copied scripts so you can tweak them. At this point you’re on your own.

You don’t have to ever use `eject`. The curated feature set is suitable for small and middle deployments, and you
shouldn’t feel obligated to use this feature. However we understand that this tool wouldn’t be useful if you couldn’t
customize it when you are ready for it.

## Инициализация плагина

```
var sdk = new MercurySDK({
  checkoutUrl: '/create-transaction',
  statusUrl: '/check-status',
  checkStatusInterval: 2000,
  mount: '#mercury-cash',
  lang: 'en',
  limits: {
      BTC: 100,
      ETH: 50,
      DASH: 150
    }
})
```

В конструктор MercurySDK передаем объект с нужными параметрами:

* `checkoutUrl` - принимает текст, должно возвращать объект `response.data.data`, `response.data` это корень ответа,
  ваши результаты должны быть в объекте `data` в корне
* `statusUrl` - принимает текст
* `checkStatusInterval` - интервал для отправки запросов на проверку статуса транзакции
* `mount` - принимает текст для монтирования модального окна в DOM, должен быть обязательно ID DOM элемента внутри body
* `lang` - принимает текст языка в виде `en` или тот язык клиента который делает заказ. Если указанный язык не найдет,
  то язык автоматом поменяется на Английский

### Запрос на `checkoutUrl`

`checkoutUrl` на которого будет отправляться post запрос с параметрами: `price` цена, `crypto` криптовалюта который
выбрал клиент, `currency` валюта клиента на котором он хочет оплатить и `email`. Запрос будет отправляться после выбора
криптовалюты.

Запрос должен вернуть:

- `cryptoAmount`
- `confirmations`
- `address`
- `qrCodeText`
- `exchangeRate`
- `networkFee`
- `uuid`
- `cryptoCurrency`

### Запрос на `statusUrl`

Будет отправлять post запрос для получения статуса транзакции со след. параметрами:

* `uuid` id транзакции

Запросы будут отправляться каждые N секунд которые указаны в `checkStatusInterval`
В файле `./src/config/statuses.js` есть 2 константы.

* `TRANSACTION_RECEIVED` - будет ожидать когда транзакцию оплатят шаг с QR кодом, если запрос на `statusUrl`
  вернут `TRANSACTION_RECEIVED` то модальное окно перейдет на ожидания одобрения транзакции
* `TRANSACTION_APROVED` - Этот статус будет ожидать одобрения трансакции, когда запрос вернет `TRANSACTION_APROVED`
  тогда уже модальное окно перейдет на благодарность за покупку.

Помимо `status` запрос на `statusUrl` должен вернуть `confirmations`

## Вызов плагина

```
sdk.checkout(price, currency)
```

У конструктора есть метод `checkout` для вызова модального окна который принимает 2 аргумента

* `price` - Принимает число сумму заказа, это параметр `price = 100` который отправляется на `checkoutUrl`
* `currency` - Принимает текст, валюту заказа, это параметр `currency = USD || EUR` который отправляется
  на `checkoutUrl`
* `email` - Принимает текст email заказчика, отправляется на `checkoutUrl` в качестве параметра

## Callback events

После инициализации плагина можно будет вызвать callback события через `sdk.on('close', (obj) => console.log(obj))`

* `sdk.on('close', (obj) => console.log(obj))` - подписка на событие `close` будет срабатывать когда модальное окно
  закроется. Он может вернуть пустой объект, если модальное окно было закрыта в этапе выбора криптовалюты, или вернуть
  объект на подобии:

``` 
{
  address: "0x521273d0a5b93fbb4001c40b12d88e632ebeee8b"
  confirmations: 3
  cryptoAmount: 0.04230942
  cryptoCurrency: "ETH"
  exchangeRate: "1,182.77"
  networkFee: "0.000102891241761"
  qrCodeText: "SomeQRCode"
  status: "TRANSACTION_APROVED"
  uuid: "someUUID"
}

```

все данные кроме `status` придут после выбора криптовалюты, а `status` будет меняться в процессе транзакции в
зависимости от полученного статуса при отправке запроса на `statusUrl`

## PUBLIC_URL

`PUBLIC_URL` - параметр в .env надо прописать этот параметр на тот ресурс откуда будут загружаться статика, с файлы
должны быть такой же структуры как в /build папке

## Localization

Можно добавлять доп. локализации для модалки, их можно точно так же описывать в файле `in18.js` в
папке `src/translations`

## Dev mode

Чтобы запустить проект в `development` режиме нужно в файле `.env` значение переменной `REACT_APP_DEVELOPMENT` нужно
поменять на `development`.