<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Ansys\AnsysSpItemRepository")
 * @ORM\Table(name="ansys_sp_item")
 */
class AnsysSpItem
{

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * @ORM\Column(name="monday_item_id", type="string", length=255)
     */
    protected string $monday_item_id;

    /**
     * @ORM\Column(name="monday_item_name", type="string", length=255)
     */
    protected string $monday_item_name;

    /**
     * @ORM\Column(name="project_id", type="string", length=255, nullable=true)
     */
    protected ?string $project_id;

    /**
     * @ORM\Column(name="consumed_update_date", type="string", length=255, nullable=true)
     */
    protected ?string $consumed_update_date;

    /**
     * @ORM\Column(name="sp_name", type="string", length=255, nullable=true)
     */
    protected ?string $sp_name;

    /**
     * @ORM\Column(name="sp_po_no", type="string", length=255, nullable=true)
     */
    protected ?string $sp_po_no;

    /**
     * @ORM\Column(name="ansys_customer_name", type="string", length=255, nullable=true)
     */
    protected ?string $ansys_customer_name;

    /**
     * @ORM\Column(name="measurement_reporting_unit", type="string", length=255, nullable=true)
     */
    protected ?string $measurement_reporting_unit;

    /**
     * @ORM\Column(name="project_task", type="string", length=255, nullable=true)
     */
    protected ?string $project_task;

    /**
     * @ORM\Column(name="project_activity", type="string", length=255, nullable=true)
     */
    protected ?string $project_activity;

    /**
     * @ORM\Column(name="planned", type="float", nullable=true)
     */
    protected ?float $planned;

    /**
     * @ORM\Column(name="baseline", type="float", nullable=true)
     */
    protected ?float $baseline;

    /**
     * @ORM\Column(name="consumed", type="float", nullable=true)
     */
    protected ?float $consumed;

    /**
     * @ORM\Column(name="sp_rate", type="float", nullable=true)
     */
    protected ?float $sp_rate;

    /**
     * @ORM\Column(name="sp_currency", type="string", length=255, nullable=true)
     */
    protected ?string $sp_currency;

    /**
     * @ORM\Column(name="project_start_date", type="string", length=255, nullable=true)
     */
    protected ?string $project_start_date;

    /**
     * @ORM\Column(name="project_end_date", type="string", length=255, nullable=true)
     */
    protected ?string $project_end_date;

    /**
     * @ORM\Column(name="sp_resource_type", type="string", length=255, nullable=true)
     */
    protected ?string $sp_resource_type;

    /**
     * @ORM\Column(name="sp_unique_id", type="string", length=255, nullable=true)
     */
    protected ?string $sp_unique_id;

    /**
     * @ORM\Column(name="executive_summary", type="string", length=255, nullable=true)
     */
    protected ?string $executive_summary;

    /**
     * @ORM\Column(name="project_name", type="string", length=255, nullable=true)
     */
    protected ?string $project_name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): AnsysSpItem
    {
        $this->id = $id;
        return $this;
    }

    public function getMondayItemId(): string
    {
        return $this->monday_item_id;
    }

    public function setMondayItemId(string $monday_item_id): AnsysSpItem
    {
        $this->monday_item_id = $monday_item_id;
        return $this;
    }

    public function getMondayItemName(): string
    {
        return $this->monday_item_name;
    }

    public function setMondayItemName(string $monday_item_name): AnsysSpItem
    {
        $this->monday_item_name = $monday_item_name;
        return $this;
    }

    public function getProjectId(): ?string
    {
        return $this->project_id;
    }

    public function setProjectId(?string $project_id): AnsysSpItem
    {
        $this->project_id = $project_id;
        return $this;
    }

    public function getConsumedUpdateDate(): ?string
    {
        return $this->consumed_update_date;
    }

    public function setConsumedUpdateDate(?string $consumed_update_date): AnsysSpItem
    {
        $this->consumed_update_date = $consumed_update_date;
        return $this;
    }

    public function getSpName(): ?string
    {
        return $this->sp_name;
    }

    public function setSpName(?string $sp_name): AnsysSpItem
    {
        $this->sp_name = $sp_name;
        return $this;
    }

    public function getSpPoNo(): ?string
    {
        return $this->sp_po_no;
    }

    public function setSpPoNo(?string $sp_po_no): AnsysSpItem
    {
        $this->sp_po_no = $sp_po_no;
        return $this;
    }

    public function getAnsysCustomerName(): ?string
    {
        return $this->ansys_customer_name;
    }

    public function setAnsysCustomerName(?string $ansys_customer_name): AnsysSpItem
    {
        $this->ansys_customer_name = $ansys_customer_name;
        return $this;
    }

    public function getMeasurementReportingUnit(): ?string
    {
        return $this->measurement_reporting_unit;
    }

    public function setMeasurementReportingUnit(?string $measurement_reporting_unit): AnsysSpItem
    {
        $this->measurement_reporting_unit = $measurement_reporting_unit;
        return $this;
    }

    public function getProjectTask(): ?string
    {
        return $this->project_task;
    }

    public function setProjectTask(?string $project_task): AnsysSpItem
    {
        $this->project_task = $project_task;
        return $this;
    }

    public function getProjectActivity(): ?string
    {
        return $this->project_activity;
    }

    public function setProjectActivity(?string $project_activity): AnsysSpItem
    {
        $this->project_activity = $project_activity;
        return $this;
    }

    public function getPlanned(): ?float
    {
        return $this->planned;
    }

    public function setPlanned(?float $planned): AnsysSpItem
    {
        $this->planned = $planned;
        return $this;
    }

    public function getBaseline(): ?float
    {
        return $this->baseline;
    }

    public function setBaseline(?float $baseline): AnsysSpItem
    {
        $this->baseline = $baseline;
        return $this;
    }

    public function getConsumed(): ?float
    {
        return $this->consumed;
    }

    public function setConsumed(?float $consumed): AnsysSpItem
    {
        $this->consumed = $consumed;
        return $this;
    }

    public function getSpRate(): ?float
    {
        return $this->sp_rate;
    }

    public function setSpRate(?float $sp_rate): AnsysSpItem
    {
        $this->sp_rate = $sp_rate;
        return $this;
    }

    public function getSpCurrency(): ?string
    {
        return $this->sp_currency;
    }

    public function setSpCurrency(?string $sp_currency): AnsysSpItem
    {
        $this->sp_currency = $sp_currency;
        return $this;
    }

    public function getProjectStartDate(): ?string
    {
        return $this->project_start_date;
    }

    public function setProjectStartDate(?string $project_start_date): AnsysSpItem
    {
        $this->project_start_date = $project_start_date;
        return $this;
    }

    public function getProjectEndDate(): ?string
    {
        return $this->project_end_date;
    }

    public function setProjectEndDate(?string $project_end_date): AnsysSpItem
    {
        $this->project_end_date = $project_end_date;
        return $this;
    }

    public function getSpResourceType(): ?string
    {
        return $this->sp_resource_type;
    }

    public function setSpResourceType(?string $sp_resource_type): AnsysSpItem
    {
        $this->sp_resource_type = $sp_resource_type;
        return $this;
    }

    public function getSpUniqueId(): ?string
    {
        return $this->sp_unique_id;
    }

    public function setSpUniqueId(?string $sp_unique_id): AnsysSpItem
    {
        $this->sp_unique_id = $sp_unique_id;
        return $this;
    }

    public function getExecutiveSummary(): ?string
    {
        return $this->executive_summary;
    }

    public function setExecutiveSummary(?string $executive_summary): AnsysSpItem
    {
        $this->executive_summary = $executive_summary;
        return $this;
    }

    public function getProjectName(): ?string
    {
        return $this->project_name;
    }

    public function setProjectName(?string $project_name): AnsysSpItem
    {
        $this->project_name = $project_name;
        return $this;
    }

}
