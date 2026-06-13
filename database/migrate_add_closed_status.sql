-- Добавляем статус «Сделано» (задача закрыта руководителем, уходит в архив)
INSERT INTO `task_statuses` (`code`, `name`, `sort_order`) VALUES ('closed', 'Сделано', 4);
