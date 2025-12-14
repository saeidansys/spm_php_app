<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Ansys\AnsysSpItemSnapshotRepository")
 * @ORM\Table(name="ansys_sp_item_snapshot")
 */
class AnsysSpItemSnapshot
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
     * @ORM\Column(name="ansys_customer_name", type="string", length=255, nullable=true)
     */
    protected ?string $ansys_customer_name;

    /**
     * @ORM\Column(name="sp_po_no", type="string", length=255, nullable=true)
     */
    protected ?string $sp_po_no;

    /**
     * @ORM\Column(name="snapshot_date", type="string", length=255, nullable=true)
     */
    protected ?string $snapshot_date;

    /**
     * @ORM\Column(name="measurement_reporting_unit", type="string", length=255, nullable=true)
     */
    protected ?string $measurement_reporting_unit;

    /**
     * @ORM\Column(name="sp_currency", type="string", length=255, nullable=true)
     */
    protected ?string $sp_currency;

    /**
     * @ORM\Column(name="baseline", type="float", nullable=true)
     */
    protected ?float $baseline;

    /**
     * @ORM\Column(name="planned", type="float", nullable=true)
     */
    protected ?float $planned;

    /**
     * @ORM\Column(name="consumed", type="float", nullable=true)
     */
    protected ?float $consumed;

    /**
     * @ORM\Column(name="sp_rate", type="float", nullable=true)
     */
    protected ?float $sp_rate;

    /**
     * @ORM\Column(name="resource_type", type="string", length=255, nullable=true)
     */
    protected ?string $resource_type;

    /**
     * @ORM\Column(name="resource_group", type="string", length=255, nullable=true)
     */
    protected ?string $resource_group;

    /**
     * @ORM\Column(name="sp_name", type="string", length=255, nullable=true)
     */
    protected ?string $sp_name;

    /**
     * @ORM\Column(name="sp_unique_id", type="string", length=255, nullable=true)
     */
    protected ?string $sp_unique_id;

    /**
     * @ORM\Column(name="project_name", type="string", length=255, nullable=true)
     */
    protected ?string $project_name;

    /**
     * @ORM\Column(name="consumed_update_date", type="string", length=255, nullable=true)
     */
    protected ?string $consumed_update_date;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): AnsysSpItemSnapshot
    {
        $this->id = $id;
        return $this;
    }

    public function getMondayItemId(): string
    {
        return $this->monday_item_id;
    }

    public function setMondayItemId(string $monday_item_id): AnsysSpItemSnapshot
    {
        $this->monday_item_id = $monday_item_id;
        return $this;
    }

    public function getMondayItemName(): string
    {
        return $this->monday_item_name;
    }

    public function setMondayItemName(string $monday_item_name): AnsysSpItemSnapshot
    {
        $this->monday_item_name = $monday_item_name;
        return $this;
    }

    public function getAnsysCustomerName(): ?string
    {
        return $this->ansys_customer_name;
    }

    public function setAnsysCustomerName(?string $ansys_customer_name): AnsysSpItemSnapshot
    {
        $this->ansys_customer_name = $ansys_customer_name;
        return $this;
    }

    public function getSpPoNo(): ?string
    {
        return $this->sp_po_no;
    }

    public function setSpPoNo(?string $sp_po_no): AnsysSpItemSnapshot
    {
        $this->sp_po_no = $sp_po_no;
        return $this;
    }

    public function getSnapshotDate(): ?string
    {
        return $this->snapshot_date;
    }

    public function setSnapshotDate(?string $snapshot_date): AnsysSpItemSnapshot
    {
        $this->snapshot_date = $snapshot_date;
        return $this;
    }

    public function getMeasurementReportingUnit(): ?string
    {
        return $this->measurement_reporting_unit;
    }

    public function setMeasurementReportingUnit(?string $measurement_reporting_unit): AnsysSpItemSnapshot
    {
        $this->measurement_reporting_unit = $measurement_reporting_unit;
        return $this;
    }

    public function getSpCurrency(): ?string
    {
        return $this->sp_currency;
    }

    public function setSpCurrency(?string $sp_currency): AnsysSpItemSnapshot
    {
        $this->sp_currency = $sp_currency;
        return $this;
    }

    public function getBaseline(): ?float
    {
        return $this->baseline;
    }

    public function setBaseline(?float $baseline): AnsysSpItemSnapshot
    {
        $this->baseline = $baseline;
        return $this;
    }

    public function getPlanned(): ?float
    {
        return $this->planned;
    }

    public function setPlanned(?float $planned): AnsysSpItemSnapshot
    {
        $this->planned = $planned;
        return $this;
    }

    public function getConsumed(): ?float
    {
        return $this->consumed;
    }

    public function setConsumed(?float $consumed): AnsysSpItemSnapshot
    {
        $this->consumed = $consumed;
        return $this;
    }

    public function getSpRate(): ?float
    {
        return $this->sp_rate;
    }

    public function setSpRate(?float $sp_rate): AnsysSpItemSnapshot
    {
        $this->sp_rate = $sp_rate;
        return $this;
    }

    public function getResourceType(): ?string
    {
        return $this->resource_type;
    }

    public function setResourceType(?string $resource_type): AnsysSpItemSnapshot
    {
        $this->resource_type = $resource_type;
        return $this;
    }

    public function getResourceGroup(): ?string
    {
        return $this->resource_group;
    }

    public function setResourceGroup(?string $resource_group): AnsysSpItemSnapshot
    {
        $this->resource_group = $resource_group;
        return $this;
    }

    public function getSpName(): ?string
    {
        return $this->sp_name;
    }

    public function setSpName(?string $sp_name): AnsysSpItemSnapshot
    {
        $this->sp_name = $sp_name;
        return $this;
    }

    public function getSpUniqueId(): ?string
    {
        return $this->sp_unique_id;
    }

    public function setSpUniqueId(?string $sp_unique_id): AnsysSpItemSnapshot
    {
        $this->sp_unique_id = $sp_unique_id;
        return $this;
    }

    public function getProjectName(): ?string
    {
        return $this->project_name;
    }

    public function setProjectName(?string $project_name): AnsysSpItemSnapshot
    {
        $this->project_name = $project_name;
        return $this;
    }

    public function getConsumedUpdateDate(): ?string
    {
        return $this->consumed_update_date;
    }

    public function setConsumedUpdateDate(?string $consumed_update_date): AnsysSpItemSnapshot
    {
        $this->consumed_update_date = $consumed_update_date;
        return $this;
    }

}
