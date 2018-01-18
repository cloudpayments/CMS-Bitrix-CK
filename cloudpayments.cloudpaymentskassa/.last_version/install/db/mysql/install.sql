create table if not exists vbch_cld_kassa
(
	ID int(11) not null auto_increment,
	TIMESTAMP_X timestamp not null,
	LID char(2) not null,
	ORDER_ID int(11) not null,
	PAY_ID int(11) not null,
	STATUS char(1) not null DEFAULT 'N',
	SUMMA decimal(18,4) not null default '0.0',
	URLCHECK char(250),
	URLQRCODE char(250),
	TYPE char(15),
	DOCUMENT char(10),
	TRANSACTION char(50),
	DATACHECK char(20),
	ACCOUNTID int(11),
	INN char(15),
	OFD char(50),
	SESSIONNUMBER char(50),
	FISCALSIGH char(50),
	DEVICENUMBER char(50),
	REGNUMBER char(50),
	PRIMARY KEY (ID)
);
