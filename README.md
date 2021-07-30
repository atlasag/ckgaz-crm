<p align="center"><img height="188" width="198" src="https://botman.io/img/botman.png"></p>
<h1 align="center">Telegram-бот <==> Bitrix24</h1>

## About ckgaz-crm

Telegram-бот - посредник между Telegram и CRM Bitrix24. 
Сделан с использованием BotMan Studio [http://botman.io](http://botman.io).

## Принцип работы

Менеджер на некотором шаге воронки Bitrix24 назначает сделку на полевого инженера (пользователя бота)

Bitrix24 по api отправляет информацию о сделке в данное приложение, которое транслирует его нужному пользователю.

Приложение ждет реакции от пользователя (нажатия кнопки подтвержения и т.п.), и затем по api меняет некоторые атрибуты у сделки в Bitrix.

