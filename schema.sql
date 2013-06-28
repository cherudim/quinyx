SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE SCHEMA IF NOT EXISTS `quinyx` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
USE `quinyx` ;

-- -----------------------------------------------------
-- Table `quinyx`.`address`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `quinyx`.`address` ;

CREATE  TABLE IF NOT EXISTS `quinyx`.`address` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `street` VARCHAR(64) NULL ,
  `zip` VARCHAR(5) NULL ,
  `city` VARCHAR(32) NULL ,
  `country` VARCHAR(32) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `quinyx`.`employee`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `quinyx`.`employee` ;

CREATE  TABLE IF NOT EXISTS `quinyx`.`employee` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(64) NULL ,
  `address_id` INT UNSIGNED NOT NULL ,
  `start_at` TIMESTAMP NOT NULL ,
  `end_at` TIMESTAMP NULL ,
  `email` VARCHAR(64) NULL ,
  `phone` VARCHAR(16) NULL ,
  `born_at` TIMESTAMP NULL ,
  `unit_id` INT UNSIGNED NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_employee_address1` (`address_id` ASC) ,
  INDEX `fk_employee_unit1` (`unit_id` ASC) ,
  CONSTRAINT `fk_employee_address1`
    FOREIGN KEY (`address_id` )
    REFERENCES `quinyx`.`address` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_employee_unit1`
    FOREIGN KEY (`unit_id` )
    REFERENCES `quinyx`.`unit` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `quinyx`.`unit`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `quinyx`.`unit` ;

CREATE  TABLE IF NOT EXISTS `quinyx`.`unit` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `address_id` INT UNSIGNED NOT NULL ,
  `name` VARCHAR(64) NULL ,
  `description` TEXT NULL ,
  `chief_employee_id` INT UNSIGNED NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_unit_address` (`address_id` ASC) ,
  INDEX `fk_unit_employee1` (`chief_employee_id` ASC) ,
  CONSTRAINT `fk_unit_address`
    FOREIGN KEY (`address_id` )
    REFERENCES `quinyx`.`address` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_unit_employee1`
    FOREIGN KEY (`chief_employee_id` )
    REFERENCES `quinyx`.`employee` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
